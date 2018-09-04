<?php
$serv = new swoole_server("0.0.0.0",30005);
$serv->set(array(
    'worker_num' => 5,
    'daemonize' => false,
    'backlog' => 128,
    'log_file' => '/data/logs/testweixin.log',
));



$serv->on('connect', function ($serv, $fd) {  
    echo "Client: Connect.\n";
});

$serv->on('receive', function ($serv, $fd, $from_id, $data) {
	$data_arr = explode("\r\n",$data);
	echo $data_arr[1];
	if($data_arr[1] == "00" ) $serv->send($fd,"56744\r\n000005\r\n100\r\n20170524156789456123\r\n08095b038a8ee35ec64dac48c06c38a6");
	else $serv->send($fd,"OK");
	echo $data;
});


$serv->on('close', function ($serv, $fd) {
    echo "Client: Close.\n";
});

//启动服务器
$serv->start(); 
