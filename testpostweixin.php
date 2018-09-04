<?php
ini_set("display_errors",1);
require_once __DIR__."/sendCardWeixin.class.php";
$post = [];
$post['money'] = 1;
$post['user_no'] = 'T566518000001' ;
$post['balance'] = 100;
$post['name'] = 201;
$post['sid'] = 56650;
$post['time'] = date("Y-m-d H:i:s");
$post['pos_no'] = 152;
$result = sendWeiXin::sendCard($post);
var_dump($result);

