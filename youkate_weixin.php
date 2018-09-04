<?php
require_once(__DIR__."/youkate_weixincheck.php");
$serv = new swoole_server("0.0.0.0",30006);
$serv->set(array(
    'worker_num' => 5,
    'daemonize' => true,
    'backlog' => 128,
     'dispatch_mode'=>3,
    'open_tcp_keepalive'=>1,
    'log_file' => '/data/logs/youkateweixin.log',
     'open_eof_check' => true, //打开EOF检测
     'package_eof' => "\r\n\r\n", //设置EOF
));

$serv->on('connect', function ($serv, $fd) {  
    echo "Client: Connect.\n";
});
// 动态的定时更换密码
$serv->on('receive', function ($serv, $fd, $from_id, $data)  {
	echo date("Y-m-d H:i:s")."/////".$data."\n";
	if(strlen($data) > 40){
		$password = "hnzf55030687";
		$data_arr = explode("\r\n",$data);
		$md5_str = substr($data,0,34);
		$info_str = substr($data,34);
		if( strtolower( trim($md5_str)) == md5($password.$info_str))
                {
			$info_arr = explode("\r\n",$info_str);		
			// 0 type
			// 1 schoolid
			// 2 kahao
			if($info_arr[0] == "00") 
			{
				$ret_info = check_money($info_arr[1],$info_arr[2]);
				if($ret_info != "error")
				{
					$_md5 = md5($password.$ret_info);
					$serv->send($fd,$ret_info."\r\n".$_md5);
				}
				else $serv->send($fd,"error104");
			}else if ($info_arr[0] == "01")
			{
				$result = check_moneyt($info_arr[1],$info_arr[2],$info_arr[4],$info_arr[5]);
				echo $result;
				if($result)
				$serv->send($fd,"OK");
				else $serv->send($fd,"error103");
			}
			else $serv->send($fd,"error102");
                }else $serv->send($fd,"error101");

	}else $serv->send($fd,"error100");
});


$serv->on('close', function ($serv, $fd) {
    echo "Client: Close.\n";
});

//启动服务器
$serv->start(); 
