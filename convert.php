#!/usr/bin/env php
<?php
echo "\r                  \r"; // if you have executed this with the php CLI (php filename), we remove the shebang
date_default_timezone_set("Europe/Stockholm");
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
define("out",dirfix(dirfix(mountpoint) . "old") . "out_" . date("Y-m-d_H-i") . ".mov");
define("ffmpeg",dirfix(getenv("HOME")) . "Downloads/ffmpeg");// absolute path to the ffmpeg binary

if(!defined("tmp")) { error("tmp is not set in configuration!");}
if(!defined("absdir")) { error("absdir is not set in configuration!");}
if(!defined("out")) { error("out is not set in configuration!");}
if(!defined("colorize")) { error("colorize is not set in configuration!");}
if(!defined("ffmpeg")) { error("ffmpeg is not set in configuration!");}

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
  info("Availability checks passed, moving forwards to getting a file list...");
}


$files = glob(absdir . "*.jpg");
$fnum = sizeof($files);

if($fnum < 10){error("Less than 10 image files, too short for a movie, exiting.");}
info("File list contains {$fnum} files.");

$in = tmp . "files.txt";
$dl = tmp . "delete.txt";
$sh = tmp . "ffmpeg.sh";
$log = tmp . "enc.log";
$h1 = fopen($in, "a") or error("Can't open {$in} for reading.");
$h2 = fopen($dl, "a") or error("Can't open {$dl} for reading.");
foreach($files as $file)
{
  fwrite($h1,"file '" . $file . "'" . PHP_EOL);
  fwrite($h2,$file . PHP_EOL);
}
fclose($h1);
fclose($h2);
succ("Wrote file list for ffmpeg to {$in}, list of files to delete to {$dl}");

$h3 = fopen($sh,"w") or error("Can't open {$sh} for writing.");
fwrite($h3,"#!/bin/bash
" . ffmpeg . " -f concat -i {$in} -vcodec h264 -strict -2 -an " . out . "
for line in \$(cat {$dl})
do
rm -v \$line
done
echo 'Removing shit but keeping log.'
rm -v {$in} {$dl} {$sh}
echo 'Exiting.'
exit 0");
fclose($h3);
chmod($sh,0755) or error("Can't set mode of {$sh}.");
succ("Successfully created shell script to call ffmpeg and delete all files and changed it's mode to 0755");

info("Executing {$sh} and letting it fork to the background. Outfile of video: " . out . " I'll write it's STDOUT and STDERR to {$log}.");
shell_exec($sh . " > {$log} 2> {$log} &");

die("Exiting." . PHP_EOL);

// trailing slash fix
function dirfix($dir)
{
  $a = substr($dir,-1) == DIRECTORY_SEPARATOR ? $dir : $dir . DIRECTORY_SEPARATOR;
  return $a;
}
// info, warn, error functions
function info($msg)
{
  echo colorize ? "\033[36m{$msg}\033[0m" . PHP_EOL : $msg . PHP_EOL;
}
function succ($msg)
{
  echo colorize ? "\033[32m{$msg}\033[0m" . PHP_EOL : $msg . PHP_EOL;
}
function warn($msg)
{
  echo colorize ? "\033[33mWarning: {$msg}\033[0m" . PHP_EOL : $msg . PHP_EOL;
}
function error($msg)
{
  die(colorize ? "\033[31mERROR: {$msg}\033[0m" . PHP_EOL : $msg . PHP_EOL);
}
?>
