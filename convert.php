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
info("this is info");
warn("shit is happening!");
error("shit happened!");
if(avail_ck)
{
  $headers = get_headers("http://" . pi);
  if(!$headers)
  {
    die("Couldn't make HEAD request to " . pi . PHP_EOL);
  }
  if($headers[0] != "HTTP/1.1 200")
  {
    die("Response code was ");
  }
}
?>
