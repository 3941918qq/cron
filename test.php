<?php
require_once(__DIR__."/protocal.php");
$serv = new swoole_server("0.0.0.0",41257 , SWOOLE_BASE, SWOOLE_SOCK_TCP);
function get_redis()
{
	$redis = new Redis();
	$redis->connect('127.0.0.1', 6379);
	return $redis;
}
$redis = get_redis();
$serv->set(array(
    'worker_num' => 5,
    'daemonize' => false,
    'backlog' => 128,
    'log_file' => '/data/logs/server.log',
));



$serv->on('connect', function ($serv, $fd) use ($redis) {  
   	 echo "Client: Connect.\n";
});

$serv->on('receive', function ($serv, $fd, $from_id, $data) {
	 $custome_arr = explode("\r\n", $data);
	 foreach ($custome_arr as $r) echo $r."\r\n";
	 $result = "OK";
	 $serv->send($fd,$result);
});


$serv->on('close', function ($serv, $fd) use ($redis) {
    	echo "Client: Close.\n";
});

$serv->start(); 
