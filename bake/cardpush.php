<?php
require_once(__DIR__."/new_server_check.php");
$serv = new swoole_server("0.0.0.0",41222 , SWOOLE_BASE, SWOOLE_SOCK_TCP);
$serv->set(array(
    'worker_num' => 5,
    'daemonize' => true,
    'backlog' => 128,
    'log_file' => '/data/logs/newsserver.log',
));

function get_redis()
{
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        return $redis;
}
$redis = get_redis();


$serv->on('connect', function ($serv, $fd)  {  
   	 echo "Client: Connect.\n";
});

$serv->on('receive', function ($serv, $fd, $from_id, $data) use ($redis) {
	 // should add key here
	 echo date("Y-m-d H:i:s")."/////////////".$data."\n";
	 $result = main($data,2);
	 //$result = main($data,2);
	 if($result) $ret = "OK";
	 else $ret = "ERROR";
	 $serv->send($fd,$ret);
});


$serv->on('close', function ($serv, $fd)   {
    	echo "Client: Close.\n";
});

$serv->start(); 
