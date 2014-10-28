#!/usr/bin/env php
<?php
echo "\r                  \r"; // if you have executed this with the php CLI (php filename), we remove the shebang
define("pi","pi.fritz.box"); // the url of pi (for avail_ck)
define("imgdir","/data/motion/imgs"); // remote directory with the imgs
define("mountpoint","/media/PI"); // where the pi is mounted on the local system
define("absmount",mountpoint . imgdir); // absolute path to the img directory on the local system
define("avail_ck",true); // check if the pi responds to http reuqests and if the mountpoint is writeable
define("tmp",sys_get_temp_dir()); // tmpdir to use
define("colorize",true); // whether to colorize output
define("exitonfailck",0); /* 0 = don't exit, just warn,
                        -1 = don't do anything,
                         1 = exit if request fails,
                         2 = same as 1 but even exit if the response code is not 200
                         */


if(avail_ck)
{
  if(!defined("pi")) { error("pi is not set in configuration!");}
  $headers = get_headers("http://" . pi);
  if(!$headers)
  {
    $lmsg = "Couldn't make HEAD request to " . pi;
    if(exitonfailck == 0){warn($lmsg);}
    elseif(exitonfailck == 1 || exitonfailck == 2){error($lmsg);}
  }
  if($headers[0] != "HTTP/1.1 200")
  {
    $lmsg = "Response code was not 200, response: {$headers[0]}";
    if(exitonfailck == 2){error($lmsg);}
    elseif(exitonfailck == 1 || exitonfailck == 0){warn($lmsg);}
  }
}

// info, warn, error functions
function info($msg)
{
  echo "\033[32mInfo: {$msg}\033[0m" . PHP_EOL;
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
