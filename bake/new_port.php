<?php
$serv = new swoole_server("0.0.0.0",41333 , SWOOLE_BASE, SWOOLE_SOCK_TCP);
$serv->set(array(
    	'worker_num' => 5,
    	'daemonize' => false,
    	'backlog' => 128,
    	'log_file' => '/data/logs/cardserver.log',
));

$serv->on('connect', function ($serv, $fd) {  
    	echo "Client: Connect.\n";
});

$serv->on('receive', function ($serv, $fd, $from_id, $data) {
	
	 if(strlen($data) > 34) 
	 {
		echo $data;
		$serv->send($fd,"ERROR");
	 }
	else $serv->send($fd,"ERROR");
});


$serv->on('close', function ($serv, $fd) {
    	echo "Client: Close.\n";
});

//启动服务器
$serv->start(); 
