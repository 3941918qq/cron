<?php
require_once(__DIR__."/wechat_qc_check.php");

$serv = new swoole_server("0.0.0.0",29999);
$serv->set(array(
    'worker_num' => 5,
    'daemonize' => true,
    'backlog' => 128,
    'log_file' => '/data/logs/wechat.log',
));



$serv->on('connect', function ($serv, $fd) {  
    echo "Client: Connect.\n";
});

$serv->on('receive', function ($serv, $fd, $from_id, $data) {

    $data=bin2hex($data);
	echo date("Y-m-d H:i:s")."/////".$data."\n";
	//查询圈存信息状况
      preg_match_all("/^fafa[0-9A-Za-z]*afaf$/",$data,$matches);  	

      if(!empty($matches[0])){
      	 $card='';
         $school16 = substr($data,8,6);//学校号3个字节
		 $position16 = substr($data,14,2);//位置
		 $order = substr($data,18,2);//命令码01
		 $card16 = substr($data,20,8);//IC卡号（16进制的物理卡号）
		 for($i = 1; $i <= strlen($card16)/2; $i++){
	           $da=substr($card16,-$i*2, 2);
	           $card.=$da;
	     }
		 $money16 = substr($data,28,8); //卡内当前余额
		 $head=substr($data,0,4);
		 $A2=substr($data,-4);

		 //进制转换
		 $sid = hexdec($school16);		
		 $phyid = hexdec($card);
		 $balance = hexdec($money16);	     
	     if($order=='01'){	
	         //查询相关数据 
	     	 $result = check_money($phyid,$sid);
	     	 $mon='';
	     	 $total='';
	     	 if($result!=0){
	     	 	$arr=array();	     	 		     	 	     	 		     	 	
	     	    foreach($result as $key=>$value){
	     	 		  $trade_no='';
	     	 		  $credit='';
	     	 		  //先处理订单号
	     	 		  // $value['trade_no']='0'.$value['trade_no'];
	     	 		  $value['trade_no']=str_pad($value['trade_no'],20,"0",STR_PAD_LEFT);
	     	 		  for($i = 1; $i <= strlen($value['trade_no'])/2; $i++){
					      $trade=substr($value['trade_no'], ($i-1)*2, 2);  
					      $trade16=dechex($trade);
					      $trade16=str_pad($trade16,2,"0",STR_PAD_LEFT);  
					      $trade_no.=$trade16;	   
				      	        	       	   	  
				      }
				      //再处理价格
				      $credit16=dechex($value['credit']*100);
				      $credit16=str_pad($credit16,8,"0",STR_PAD_LEFT); 
				      for ($i = 1; $i <= strlen($credit16)/2; $i++){
			               $credit.=substr($credit16, -$i*2, 2);
			          }
	     	 	      $ic=$credit.$trade_no;
			          $arr[$key]=$ic;
		              $mon+=$value['credit']*100;
			    }
			    
			    $num=dechex($mon);
			    $num=str_pad($num,8,"0",STR_PAD_LEFT);   	 
			    for ($i = 1; $i <= strlen($num)/2; $i++){
			          $total.=substr($num, -$i*2, 2);
			    }
			    $count=str_pad(dechex(count($arr)),2,"0",STR_PAD_LEFT); 
			    $ic16=implode('',$arr);

	     	 }else{
	     	 	$count='00';
	     	 	$total='00000000';
	     	 	$ic16='';
	     	 }				     
	     	 echo "-----".$phyid."------".$sid."\n";		 
			 echo date("Y-m-d H:i:s")."------".$mon."\n";
			 $date =date("YmdHis",time());
             $date16='';
	         for($i = 1; $i <= strlen($date)/2; $i++){
	             $da=substr($date,($i-1)*2, 2);
	             $date16.=str_pad(dechex($da),2,"0",STR_PAD_LEFT);
	         }
	         $strs= $school16.'01'.$count.'11'.$card16.$total.$ic16.$date16;
	         $strs_len=strlen($strs)/2;
             $strs_len=str_pad(dechex($strs_len),4,"0",STR_PAD_LEFT);
             $crc=crc($strs);	       
			 $str_send = $head.$strs_len.$strs.$crc.$A2;
			 echo date("Y-m-d H:i:s")."/////".$str_send."\n";			
			 $ar=pack("H*",$str_send);

			 $serv->send($fd,$ar);
	     }
		

      }
      //圈存成功后上报信息
      // $data='FAFA000D056669010102004e85A6FF00000020171151205081750AFAF';
      // $data=bin2hex($data);
       echo date("Y-m-d H:i:s")."///".$data."\n";
      preg_match_all("/^fafa[0-9A-Za-z]*afaf$/", $data, $matche);

		if (!empty($matche[0])) {
			$card='';
			$credit='';
			$school16 = substr($data,8,6);//学校号3个字节
			$order = substr($data,18,2);//命令码02
			$card16 = substr($data,20,8);//IC卡号（16进制的物理卡号）
			for($i = 1; $i <= strlen($card16)/2; $i++){
	           $da=substr($card16,-$i*2, 2);
	           $card.=$da;
	        }
			$money16 = substr($data,28,8); //卡内当前余额
			for ($i = 1; $i <= strlen($money16)/2; $i++){
               $credit.=substr($money16, -$i*2, 2);
            }
            $money= hexdec($credit);//当前余额
	 		$time = substr($data,36,13); //卡内当前余额
			$sid = hexdec($school16);		
		    $phyid = hexdec($card);
			if($order=='02'){
				echo "--22-------".$phyid."\n".$money."------";
       			$result = check_moneyt($phyid,$sid,$money);
			    echo date("Y-m-d H:i:s")."---".$data."------".$sid."-------".$result."\n";
			}				      			
		 }


});


$serv->on('close', function ($serv, $fd) {
    echo "Client: Close.\n";
});
//启动服务器
$serv->start();
