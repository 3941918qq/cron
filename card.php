<?php
//$exchange = getExchange();
$serv = new Swoole\Server("0.0.0.0", 45222);
$serv->set(array(
	'worker_num' =>10 , //工作进程数量
	'daemonize' => false, //是否作为守护进程
	'dispatch_mode'=>3,
    	'backlog' => 128,
	'open_tcp_keepalive'=>1,
    	'log_file' => '/data/logs/card.log',

));

$serv->on('connect', function ($serv, $fd) {
	echo date("Y-m-d H:i:s  ")."Client:Connect.\n";
});
$serv->on('receive', function ($serv, $fd, $from_id, $data)  {
	echo date("Y-m-d H:i:s  ").$data."\n";
         if(strlen($data) > 34)
         {
                $md5_str = substr($data,0,34);
                $info_str = substr($data,34);
                if( strtolower( trim($md5_str)) == md5("hnzf55030687!...".$info_str))
                {
                        //$result = $serv->exchange->publish($info_str, "card");
			echo $info_str;
                      	if(TRUE){
                                 $serv->send($fd,"OK");
                                 echo date("Y-m-d H:i:s")."SUCCESS\n";
                        }
                        else $serv->send($fd,"ERROR1\n");
                }else $serv->send($fd,"ERROR2\n");
         }
        else $serv->send($fd,"ERROR3\n");
});
$serv->on('close', function ($serv, $fd) {
	echo date("Y-m-d H:i:s  ")."Client: Close.\n";
});
$serv->on('shutdown',function($serv) {
	$serv->connection->disconnect();	
});
$serv->start();

