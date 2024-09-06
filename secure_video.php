<?php
session_start();

$str = substr($_SESSION[$_GET['quality']][$_GET['video']], strpos($_SESSION[$_GET['quality']][$_GET['video']], '/upload'));
$path = __DIR__.$str;

$size=filesize($path);
$fm=@fopen($path,'rb');
 
$begin=0;
$end=$size;
header('HTTP/1.0 200 OK');
 
header("Content-Type: video/mp4");
header('Accept-Ranges: bytes');
header('Content-Length:'.($end-$begin));
header("Content-Disposition: inline;");
header("Content-Range: bytes $begin-$end/$size");
header("Content-Transfer-Encoding: binary\n");
header('Connection: close');
 
$cur=$begin;
fseek($fm,$begin,0);
 
while(!feof($fm)&&$cur<$end&&(connection_status()==0))
{ print fread($fm,min(1024*16,$end-$cur));
  $cur+=1024*16;
  usleep(1000);
}
die();

//$path = $_SESSION[$_GET['quality']][$_GET['video']];
// header("Cache-Control: no-store, no-cache, must-revalidate");
// header('Accept-Ranges: bytes');
// header("Content-Type:video/mp4");
// $output_file=readfile($path);
// print $output_file;
