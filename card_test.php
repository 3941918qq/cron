<?php
$serv = new swoole_server("0.0.0.0",45555, SWOOLE_BASE, SWOOLE_SOCK_TCP);
$serv->set(array(
    	'worker_num' => 5,
    	'daemonize' => false,
	'open_tcp_keepalive'=>1,
    	'backlog' => 128,
    	'log_file' => '/data/logs/newservertest.log',
        'open_eof_check' => true, //打开EOF检测
        'package_eof' => "\r\n\r\n", //设置EOF
));

$serv->on('connect', function ($serv, $fd) {  
    	echo "Client: Connect.\n";
});

$serv->on('receive', function ($serv, $fd, $from_id, $data) {
	 echo $data;
	 if(strlen($data) > 34) 
	 {
		$md5_str = substr($data,0,34);
		$info_str = substr($data,34);
		echo md5("hnzf55030687!...".$info_str)."\n";
		if( strtolower( trim($md5_str)) == md5("hnzf55030687!...".$info_str))
		{
			echo "I AM SUCCESS \n";
			$serv->send($fd,"OK");
		}else $serv->send($fd,"ERROR");
	 }
	else $serv->send($fd,"ERROR");
});


$serv->on('close', function ($serv, $fd) {
    	echo "Client: Close.\n";
});

//启动服务器
$serv->start(); 
