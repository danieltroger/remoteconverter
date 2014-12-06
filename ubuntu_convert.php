#!/usr/local/php5/bin/php
<?php
echo "\r                  \r"; // if you have executed this with the php CLI (php filename), we remove the shebang

if(function_exists("pcntl_fork") && @$argv[1] == "-b")
{
  define("daemon",true);
  echo "Forking to background." . PHP_EOL;
  $pid = pcntl_fork();
  if($pid)
  {
    exit;
  }
}
else
{
  define("daemon",false);
}


define("pi","pi"); // the url of pi (for avail_ck)
define("imgdir","/motion/imgs/"); // remote directory with the imgs
define("mountpoint","/Volumes/DATA"); // where the pi is mounted on the local system
define("absdir",dirfix(mountpoint . imgdir)); // absolute path to the img directory on the local system
define("avail_ck",true); // check if the pi responds to http reuqests and if the mountpoint is writeable
define("tmp",dirfix(sys_get_temp_dir())); // tmpdir to use
define("colorize",true); // whether to colorize output
define("exitonfailck",1); /* 0 = don't exit, just warn,
                        -1 = don't do anything,
                         1 = exit if request fails,
                         2 = same as 1 but even exit if the response code is not 200
                         will always exit if the mountpoint is not writeable.
                         */
define("ffmpeg","/Users/admin/Downloads/ffmpeg");// absolute path to the ffmpeg binary
define("logfile", tmp . "convert.log");
define("timezone","Europe/Stockholm");
date_default_timezone_set(timezone);
define("out",dirfix(dirfix(mountpoint) . "old") . "out_" . date("Y-m-d_H-i") . ".mov");
define("min",50); // minimum amount of image files

function mount()
{
  if(!is_dir(mountpoint))
  {
    mkdir(mountpoint);
  }
  while(@$pass == "undefined" || strlen(@$pass) < 1)
  {
    echo "Please enter the password for the AFP server: ";
    $stdin = fopen("php://stdin","r");
    $pass = substr(fgets($stdin),0,-1); // get one line and remove the trailing newline
    fclose($stdin);
  }
  shell("mount_afp afp://pi:{$pass}@pi.fritz.box/DATA " . mountpoint);
  if(in_array(mountpoint,mnts()))
  {
    return true;
  }
  else
  {
    return false;
  }
}

if(daemon) {$log = fopen(logfile,"w") or die("Could not open logfile " . logfile . " for writing.");}

$checks = Array("min","tmp","ffmpeg","colorize","timezone","out","logfile","avail_ck","absdir","imgdir","mountpoint");
foreach($checks as $check) { if(!defined($check)) { error($check . " is not defined in configuration!");}}
if(!file_exists(ffmpeg) || !is_executable(ffmpeg)) {error("Couldn't find or execute the ffmpeg binary " . ffmpeg . ", make sure the correct path is used and the file is marked as executeable.");}

if(avail_ck)
{
  info("Checking network reachability...");
  if(!defined("pi")) { error("pi is not set in configuration!");}
  if(!defined("exitonfailck")) { error("exitonfailck is not set in configuration!");}
  $headers = get_headers("http://" . pi);
  if(!$headers)
  {
    $lmsg = "Couldn't make HEAD request to " . pi;
    if(exitonfailck == 0){warn($lmsg);}
    elseif(exitonfailck == 1 || exitonfailck == 2){error($lmsg);}
  }
  if(strpos($headers[0],"HTTP/1.1 200") !== false)
  {
    succ("Alright, network reachability check passed!");
  }
  else
  {
    $lmsg = "Response code was not 200, response: {$headers[0]}";
    if(exitonfailck == 2){error($lmsg);}
    elseif(exitonfailck == 1 || exitonfailck == 0){warn($lmsg);}
  }
  info("Checking if mountpoint is writeable...");
  if(is_writable(mountpoint))
  {
    succ("Alright, mountpoint is writeable!");
  }
  else
  {
    if(function_exists("mount") && !daemon)
    {
      warn("Mountpoint doesn't exist or is not writeable.");
      info("mount() hook function found. Executing.");
      if(mount())
      {
        succ("Mount succeeded. Continuing.");
      }
      else
      {
        error("Mount hook function failed. Exiting.");
      }
    }
    else
    {
      error("Mountpoint doesn't exist or is not writeable. Exiting.");
    }
  }
  info("Availability checks passed, moving forwards to getting a file list...");
}


$files = glob(absdir . "*.jpg");
$fnum = sizeof($files);

if($fnum < min){error("Less than " . min . " image files, too short for a movie, exiting.");}
info("File list contains {$fnum} files.");

$in = tmp . "files.txt";
$h = fopen($in, "w") or error("Can't open {$in} for writing.");
foreach($files as $file)
{
  if(file_exists($file))
  {
    fwrite($h,"file '" . $file . "'" . PHP_EOL);
  }
}
fclose($h);
succ("Wrote file list for ffmpeg to {$in}.");

info("Executing ffmpeg. Outfile of video: " . out . PHP_EOL);

shell(ffmpeg . " -f concat -i {$in} -vcodec h264 -strict -2 -an " . out . " 2>&1","r");

info("");
if(file_exists(out) && filesize(out) > 0)
{
  succ("FFMpeg executed.");
}
else
{
  error("Something went wrong.");
}

$files[] = $in;
$clean = "";
for($i = 0; $i < 50; $i ++) { $clean .= " ";} $clean .= "\r";

foreach($files as $file)
{
  if(!daemon)
  {
    echo $clean;
    echo "Deleting " . basename($file) . "\r";
  }
  @unlink($file);
}

info("");
succ("Deleted image files.");

daemon ? fclose($log) : null;

exit;

// trailing slash fix
function dirfix($dir)
{
  $a = substr($dir,-1) == DIRECTORY_SEPARATOR ? $dir : $dir . DIRECTORY_SEPARATOR;
  return $a;
}
// info, warn, error functions
function info($msg)
{
  if(daemon)
  {
    fwrite($GLOBALS['log'],$msg . PHP_EOL);
  }
  else
  {
    echo colorize ? "\033[36m{$msg}\033[0m" . PHP_EOL : $msg . PHP_EOL;
  }
}
function succ($msg)
{
  if(daemon)
  {
    fwrite($GLOBALS['log'],$msg . PHP_EOL);
  }
  else
  {
    echo colorize ? "\033[32m{$msg}\033[0m" . PHP_EOL : $msg . PHP_EOL;
  }
}
function warn($msg)
{
  if(daemon)
  {
    fwrite($GLOBALS['log'],$msg . PHP_EOL);
  }
  else
  {
    echo colorize ? "\033[33mWarning: {$msg}\033[0m" . PHP_EOL : $msg . PHP_EOL;
  }
}
function error($msg)
{
  if(daemon)
  {
    fwrite($GLOBALS['log'],$msg . PHP_EOL);
    posix_kill(posix_getpid(), SIGHUP);
  }
  else
  {
  die(colorize ? "\033[31mERROR: {$msg}\033[0m" . PHP_EOL : $msg . PHP_EOL);
  }
}
function shell($cmd)
{
  $sh = popen($cmd,"r");
  while(!feof($sh))
  {
    $buff = fread($sh,1024);
    if(daemon)
    {
      fwrite($GLOBALS['log'],$buff);
    }
    else
    {
      echo $buff;
    }
  }
  pclose($sh);
}
function mnts() // returns all currently mounted directories
{
  $b = Array();
  $c = shell_exec("mount");
  foreach(explode(PHP_EOL,$c) as $d)
  {
    $e = explode(" ",$d);
    for($f = 0;$f<sizeof($e)-1;$f++)
    {
      if($e[$f] == "on")
      {
        $b[$e[0]] = Array();
        $g = "";
        for($h = 1;($e[$f+$h][0] != "(");$h++)
        {
          $g .= " " . $e[$f+$h];
        }
        $g = substr($g,1);
        $b[$e[0]] = $g;
      }
    }
  }
  return $b;
}
?>
