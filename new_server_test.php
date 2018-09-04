<?php
require_once (__DIR__."/new_server_check.php");
$info = "56650\r\n3421,192.168.1.61,20170915080120,15,1,10000,100,9900,39015,2,0,5200817051301221,71";
$result = main($info);
var_dump($result);
