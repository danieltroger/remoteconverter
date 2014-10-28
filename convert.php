#!/usr/bin/env php
<?php
echo "\r                  \r"; // if you have executed this with the php CLI (php filename), we remove the shebang
define("pi","pi"); // the url of pi (for avail_ck)
define("imgdir","/data/motion/imgs/"); // remote directory with the imgs
define("mountpoint","/media/PI"); // where the pi is mounted on the local system
define("absdir",dirfix(mountpoint . imgdir)); // absolute path to the img directory on the local system
define("avail_ck",true); // check if the pi responds to http reuqests and if the mountpoint is writeable
define("tmp",sys_get_temp_dir()); // tmpdir to use
define("itmp",dirfix(dirfix(tmp) . "cpy")); // subdir in tmp
define("colorize",true); // whether to colorize output
define("exitonfailck",1); /* 0 = don't exit, just warn,
                        -1 = don't do anything,
                         1 = exit if request fails,
                         2 = same as 1 but even exit if the response code is not 200
                         will always exit if the mountpoint is not writeable.
                         */

if(avail_ck)
{
  info("Checking network reachability...");
  if(!defined("pi")) { error("pi is not set in configuration!");}
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
  if(is_writable( mountpoint ))
  {
    succ("Alright, mountpoint is writeable!");
  }
  else
  {
    error("Mountpoint doesn't exist or is not writeable. Exiting.");
  }
  info("Availability checks passed, moving forwards to getting a file list and copying...");
}

if(!defined("tmp")) { error("tmp is not set in configuration!");}
if(!defined("absdir")) { error("absdir is not set in configuration!");}

$files = glob(absdir . "*.jpg");
succ("File list created.");

info("Going to move all files to the local computer");
if(!is_dir(itmp)){mkdir(itmp);}
foreach($files as $nname => $file)
{
  $dest = itmp . $nname . ".jpg";
  info("Copying {$file} to {$dest}...");
  if(copy($file,$dest))
  {
    succ(basename($file) . " successfully copied!");
  }
  else
  {
    error("Something went wrong while copying.");
  }
}
// trailing slash fix
function dirfix($dir)
{
  $a = substr($dir,-1) == DIRECTORY_SEPARATOR ? $dir : $dir . DIRECTORY_SEPARATOR;
  return $a;
}
// info, warn, error functions
function info($msg)
{
  echo $msg . PHP_EOL;
}
function succ($msg)
{
  echo "\033[32m{$msg}\033[0m" . PHP_EOL;
}
function warn($msg)
{
  echo "\033[33mWarning: {$msg}\033[0m" . PHP_EOL;
}
function error($msg)
{
  die("\033[31mERROR: {$msg}\033[0m" . PHP_EOL);
}
?>
