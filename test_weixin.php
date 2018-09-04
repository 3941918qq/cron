<?php
require_once(__DIR__."/weixin_check.php");
$serv = new swoole_server("0.0.0.0",30001 , SWOOLE_BASE, SWOOLE_SOCK_TCP);
$serv->set(array(
    'worker_num' => 5,
    'daemonize' => true,
    'backlog' => 128,
    'log_file' => '/data/logs/weixin.log',
));



$serv->on('connect', function ($serv, $fd) {  
    echo "Client: Connect.\n";
});

$serv->on('receive', function ($serv, $fd, $from_id, $data) {
	//$result = check_money($data);
	$serv->send($fd,100);
});


$serv->on('close', function ($serv, $fd) {
    echo "Client: Close.\n";
});

//启动服务器
$serv->start(); 
