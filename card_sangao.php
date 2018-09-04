<?php
$serv = new Swoole\Server("0.0.0.0", 45223);
$serv->set(array(
	'worker_num' =>5 , //工作进程数量
	'daemonize' => true, //是否作为守护进程
	'dispatch_mode'=>3,
    	'backlog' => 128,
	'open_tcp_keepalive'=>1,
    	'log_file' => '/data/logs/sangao-card.log',
	'open_eof_check' => true, //打开EOF检测
	'package_eof' => "\r\n\r\n", //设置EOF

));

$serv->on('workerstart', function($serv, $id) {
	$conn_args = array(
                'host' => '127.0.0.1', //rabbitmq 服务器host
                'port' => 5672, //rabbitmq 服务器端口
                'login' => 'guest', //登录用户
                'password' => 'hnzf55030687', //登录密码
                'vhost' => '/', //虚拟主机
        );
        $e_name = 'card';
        $q_name = 'card';
        //$msg = "helloworld";

        $conn = new AMQPConnection($conn_args);
        if (!$conn->connect()) {
                die('Cannot connect to the broker');
        }
        $channel = new AMQPChannel($conn);

        $ex = new AMQPExchange($channel);
        $ex->setName($e_name);
        $ex->setType(AMQP_EX_TYPE_DIRECT);
        $ex->setFlags(AMQP_DURABLE);
        $status = $ex->declareExchange(); //声明一个新交换机，如果这个交换机已经存在了，就不需要再调用declareExchange()方法了.
	/*
        $q = new AMQPQueue($channel);
        $q->setName($q_name);
	$q->setFlags(AMQP_DURABLE  );
        $status = $q->declareQueue(); //同理如果该队列已经存在不用再调用这个方法了。
        $q->bind($e_name,"card");
	*/
	$serv->exchange = $ex;
	$serv->connection = $conn;

});


$serv->on('connect', function ($serv, $fd) {
	echo date("Y-m-d H:i:s  ")."Client:Connect.\n";
});
$serv->on('receive', function ($serv, $fd, $from_id, $data)  {
	echo date("Y-m-d H:i:s  ").$data."\n";
         if(preg_match("/^[0-9a-z]{32}\r\n[0-9]{5}\r\n(.+\r\n)+\r\n$/",$data))
         {
                $md5_str = substr($data,0,34);
                $info_str = substr($data,34);
                if( strtolower( trim($md5_str)) == md5("hnzf55030687!...".$info_str))
                {
                        $result = $serv->exchange->publish($info_str, "card");
                        if($result){
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
/*
$serv->on('shutdown',function($serv) {
	$serv->connection->disconnect();	
});
*/
$serv->start();

