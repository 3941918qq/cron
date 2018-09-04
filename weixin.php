<?php
require_once(__DIR__."/weixin_check.php");
$serv = new swoole_server("0.0.0.0",30000 , SWOOLE_BASE, SWOOLE_SOCK_TCP);
$serv->set(array(
    'worker_num' => 5,
    'daemonize' => true,
    'backlog' => 128,
    'log_file' => '/data/logs/weixin.log',
));



$serv->on('connect', function ($serv, $fd) {  
    echo "Client: Connect.\n";
    //$client_info = $serv->getClientInfo($fd);
    //var_dump($client_info);
});

$serv->on('receive', function ($serv, $fd, $from_id, $data) {
	echo date("Y-m-d H:i:s")."/////".$data."\n";
	//$result = check_money($data,2);
	//$serv->send($fd,$result);
	///////aa 1 6 2 2 1 4e85b8 53 15 bb
	 preg_match_all("/^aa[0-9A-Za-z]*bb$/", $data, $matches);
//$zhengze ="/aa[0-9A-Za-z]bb/";
	 	 if (!empty($matches[0])) {
			 $a1 = substr($data,2,1);
			 $a2 = substr($data,3,1);
			 $a3 = substr($data,4,1);
			 $a4 = substr($data,5,1);
			 $a5 = substr($data,6,$a1); 	//学校号
			 $a6 = substr($data,$a1+6,$a2);	//卡号编号
			 $a7 = substr($data,$a1+6+$a2,$a3);//UID卡号
                  //	 $a8 = substr($data,$a1+6+$a2+$a3,$a4);	//钱
			 $a8 = substr($data,$a1+6+$a2+$a3,-2); //钱
			 $card_no = hexdec($a6);		
			 $sid = hexdec($a5);
			 $money = hexdec($a8);
			 $card_id = hexdec($a7);
			 $result = check_money($card_no,$sid,$money,$card_id);
			 echo "-----".$card_no."------".$sid."------".$card_id."\n";		 
			 echo date("Y-m-d H:i:s")."------".$result."\n";
			 $a8=dechex($result);
			 $a4=strlen($a8);
			 $strs = "cc".$a1.$a2.$a3.$a4.$a5.$a6.$a7.$a8."dd";
			 echo $strs;
			 $serv->send($fd,$strs);
		 }
		preg_match_all("/^ee[0-9A-Za-z]*ff$/", $data, $matche);
		if (!empty($matche[0])) {
			 $a1 = substr($data,2,1);
			 $a2 = substr($data,3,1);
			 $a3 = substr($data,4,1);
			 $a4 = substr($data,5,1);
			 $a5 = substr($data,6,$a1); 	//学校号
			 $a6 = substr($data,$a1+6,$a2);	//卡号
			 $a7 = substr($data,$a1+6+$a2,$a3);//UID
			// $a8 = substr($data,$a1+6+$a2+$a3,$a4);	//钱
		       	 $a8 = substr($data,$a1+6+$a2+$a3,-2);       //钱
			 $card_no = hexdec($a6);		
			 $sid = hexdec($a5);
			 $money = hexdec($a8);
	  	         $card_id = hexdec($a7);
       			 echo "--22-------".$card_no."\n".$money."------".$card_id."------";
       			 $result = check_moneyt($card_no,$sid,$money,$card_id);
			 echo date("Y-m-d H:i:s")."---".$data."------".$sid."-------".$result."\n";
			 //$a8=dechex($result);
			 //$a4=strlen($a8);
			// $strs = "cc".$a1.$a2.$a3.$a4.$a5.$a6.$a7.$a8."dd";
			// $serv->send($fd,$strs);
		 }
});


$serv->on('close', function ($serv, $fd) {
    echo "Client: Close.\n";
});

//启动服务器
$serv->start(); 
