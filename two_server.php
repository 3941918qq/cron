<?php
require_once (__DIR__."/new_server_check.php");
$serv = new swoole_server("0.0.0.0",41222 , SWOOLE_BASE, SWOOLE_SOCK_TCP);
$serv->set(array(
    	'worker_num' => 5,
    	'daemonize' => true,
    	'backlog' => 128,
    	'log_file' => '/data/logs/newserver.log',
));

$serv->on('connect', function ($serv, $fd) {  
    	echo "Client: Connect.\n";
});

$serv->on('receive', function ($serv, $fd, $from_id, $data) {
	
	 if(strlen($data) > 34) 
	 {
		$md5_str = substr($data,0,34);
		$info_str = substr($data,34);
		if( strtolower( trim($md5_str)) == md5("hnzf55030687!...".$info_str))
		{
			$result = main($info_str);
	 		if($result){
				 $serv->send($fd,"OK");
				 echo date("Y-m-d H:i:s")."SUCCESS\n";
			}
			else $serv->send($fd,"ERROR");
		}else $serv->send($fd,"ERROR");
	 }
	else $serv->send($fd,"ERROR");
});


$serv->on('close', function ($serv, $fd) {
    	echo "Client: Close.\n";
});

//启动服务器
$serv->start(); 
