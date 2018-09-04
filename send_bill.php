<?php
include_once("/data/web/ischool/mobile/web/pay/WxPayPubHelper.php");
include_once(__DIR__."/bill/ftp_upload.php");
session_start();
function getresult($appid,$mchid,$key,$sid){
        //对账单日期
        $bill_date = date("Ymd",time()-86400);          
         //使用对账单接口
        $downloadBill = new DownloadBill_pub();
         //设置对账单接口参数  
         //sign已填,商户无需重复填写
        $downloadBill->setParameter("bill_date","$bill_date");//对账单日期 
        $downloadBill->setParameter("bill_type","ALL");//账单类型 
         //对账单接口结果
        $downloadBillResult= $downloadBill->getResult($appid,$mchid,$key);
        $code=$downloadBillResult;              
        if ($code == "FAIL") {
            echo "通信出错：".$downloadBillResult['return_msg'];
        }else{            
            $res=deal_wechat_return_result($downloadBill->response);
	          //取出是餐卡的数据
            // $total=0;
            foreach($res as $k=>$v){
              $order_discount= explode('|',$v['order_discount']);
              if(trim($order_discount[count($order_discount)-1])=='ckcz'){
                // $total+=$v['order_count'];
                $res[$k]=$v;
                $res[$k]['user_no']=$order_discount[3];
                $res[$k]['school_id']=$order_discount[1];
              }else{
                unset($res[$k]);
              }
            } 
           
            $_SESSION[$sid]=$res;
            // $thirdCode='0261010503125240';
            // //头，汇总记录
            // $total=$total*100;
            // $header=$thirdCode."|".$bill_date."|".count($res)."|".$total."|".date("YdmHis",time());
            // $new_path='/data/cron/bill/';
            // //判断该用户文件夹是否已经有这个文件夹  
            // if(!file_exists($new_path)) {  
            //      mkdir($new_path,0777,true);       
            // }
            // $filepath=$new_path.$bill_date.$thirdCode.".txt";
            // file_put_contents($filepath, $header.PHP_EOL, FILE_APPEND);
            // //将数据写入文件
            // foreach ($res as $k_trade=>$v_des){
            //     //明细记录写入
            //     $order_count=$v_des['order_count']*100;
            //     $data=$v_des['order_trade']."|".$thirdCode."|0000|".$order_count."|".$v_des['user_no']."|".date("YdmHis",$v_des['order_time']);
            //     file_put_contents($filepath, $data.PHP_EOL, FILE_APPEND);
            // }
            // if(file_exists($filepath)){
            //   //使用ftp发送对账单到《一卡通》
            // }
            // var_dump($res);           
        }
    
 }
function get_db()
{
    $pdo = new PDO(
            'mysql:host=localhost;dbname=card',
            'root',
            'hnzf123456',
            array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'")
        );
        $pdo->setAttribute( PDO::ATTR_ERRMODE , PDO::ERRMODE_EXCEPTION );
        $pdo->setAttribute( PDO::ATTR_DEFAULT_FETCH_MODE , PDO::FETCH_ASSOC);
        return $pdo;
}
function deal_wechat_return_result($reponse){
    //print_r($reponse);die;
    $result = array();
    $reponse = str_replace(","," ",$reponse);
    $reponse = explode("`",$reponse);	
    $total_order_count =( count($reponse) - 6 ) / 24;
    for($i = 0; $i< $total_order_count; $i++)
    {
        $base_index = 24 * $i;
        $result[$reponse[$base_index + 7]] = array(
	          'order_trade'=>trim($reponse[$base_index + 7]),
            'order_time' => strtotime(trim($reponse[$base_index + 1])),
            'order_count' => trim($reponse[$base_index + 13]),
            'order_discount' => $reponse[$base_index + 22]
        );
    }
    return $result;
}
 //获取账户信息
$school_configs=[
      // '56650'=>[
      //     'APPID' => 'wxc5c7e311f8d5d759',
      //     'MCHID' => '1366091602',
      //     'KEY' => 'yai1ga134d4t2q1kaife8i1it6ikwaqi',
      //     'SSLCERT_PATH' => 'apiclient_cert.pem',
      //     'SSLKEY_PATH' => 'apiclient_key.pem'
      // ],
      '56744'=>[
           'APPID' => 'wx8c6755d40004036d',
           'MCHID' => '1480239942',
           'KEY' => 'ff4c0f4f0f3bae31cac8a75050c9c5f2',
           'SSLCERT_PATH' => 'apiclient_cert56651.pem',
           'SSLKEY_PATH' => 'apiclient_key56651.pem'
      ]
      // '56758'=>[
      //    'APPID' => 'wx0e02770c9fc0f131',
      //    'MCHID' => '1487744682',
      //    'KEY' => 'WGSDYGJZXCWKBMC03758169608bmcbmc',
      //    'SSLCERT_PATH' => 'apiclient_cert56758.pem',
      //    'SSLKEY_PATH' => 'apiclient_key56758.pem'
      // ]    
  ];
foreach($school_configs as $s_id=>$info)
{

	getresult($info['APPID'],$info['MCHID'],$info['KEY'],$s_id);

}
 // $res=array_merge($_SESSION['56650'],$_SESSION['56744'],$_SESSION['56758']);
 $res=$_SESSION['56744'];
 $total=0;
  foreach ($res as $key => $value) {
    $total+=$value['order_count'];
  }
  $thirdCode='510102180306020203';
  $bill_date = date("Ymd",time()-86400);
  //头，汇总记录
  $total=$total*100;
  $header=$thirdCode."|".$bill_date."|".count($res)."|".$total."|".date("YmdHis",time());
  $new_path=__DIR__.'/bill/';
  //判断该用户文件夹是否已经有这个文件夹  
  if(!file_exists($new_path)) {  
       mkdir($new_path,0777,true);       
  }
  $filepath=$new_path.$bill_date.$thirdCode.".txt";
  $filename=$bill_date.$thirdCode.".txt";
  file_put_contents($filepath, $header."\r\n", FILE_APPEND);
  // file_put_contents($filepath, $header.PHP_EOL, FILE_APPEND);
  //将数据写入文件
  foreach ($res as $k_trade=>$v_des){
      //明细记录写入
      $order_count=$v_des['order_count']*100;
      $data=$v_des['order_trade']."|".$thirdCode."|0000|".$order_count."|".$v_des['user_no']."|".date("YmdHis",$v_des['order_time']);
      file_put_contents($filepath, $data."\r\n", FILE_APPEND);
  }

  //使用ftp发送对账单到《一卡通》
  $ftp = new class_ftp();
  $ftp->up_file($filepath,$filename);
  $ftp->close();
  echo "ok";

  session_destroy();
