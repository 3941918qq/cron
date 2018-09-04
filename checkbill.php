<?php
include_once("/data/web/ischool/mobile/web/pay/WxPayPubHelper.php");
include_once("/data/web/ischool/mobile/web/pay/SendMsgs.php");
function getresult($appid,$mchid,$key){
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
        // var_dump($downloadBillResult);die;
        $code=$downloadBillResult;        
//        $downloadBillResult = $downloadBill->getResult();
//        $result=deal_wechat_return_result($downloadBillResult['return_code']);
//        echo $downloadBillResult['return_code'];        
        if ($code == "FAIL") {
            echo "通信出错：".$downloadBillResult['return_msg'];
        }else{            
            $res=deal_wechat_return_result($downloadBill->response);
	          //取出是餐卡的数据
            foreach($res as $k=>$v){
              $order_discount= explode('|',$v['order_discount']);
              if(trim($order_discount[count($order_discount)-1])=='ckcz'){
                $res[$k]=$v;
                $res[$k]['user_no']=$order_discount[3];
                $res[$k]['school_id']=$order_discount[1];
              }else{
                unset($res[$k]);
              }
            } 
            //链接数据库取数据
            $time=strtotime(date("Y-m-d"))-86400;
            $db=get_db();
            $sql="SELECT trade_no from zf_recharge_detail where time > ". $time;
            $stmt=$db->query($sql);            
            $count=$stmt->rowCount();
            for($i=0;$i<$count;$i++){
               $row=$stmt->fetch();
	             $arr[$i]=$row['trade_no'];
            }
	          //账单数据和数据库比对，能比对上的过，比对不上的存数组里面，准备存入
            $need=[];
            $ii=0;      
            // var_dump($res);
            foreach($res as $k_trade=>$v_des){
              //如果不在数据库中，存到数组

              if(!in_array(trim($k_trade),$arr)){
                
                $need[$ii]=$v_des;
                $ii++;
              }
            }
	          var_dump($need);
            if($need){	
            //查询card表取卡信息取出卡号
                foreach($need as $k=>$v){
                    if($v['school_id']==56650 || $v['school_id']==56758 || $v['school_id']==56757 || $v['school_id']==56744){
                        if(preg_match('/[a-zA-Z]/',$v['user_no'])){
                            $s_id=substr($v['user_no'],1,5);
                            $need[$k]['school_id']=$s_id;
                            $cardid= substr($v['user_no'],6);                    
                        }else{
                            // $s_id=$v['school_id'];
                            $s_id='56651';
                            $need[$k]['school_id']=$s_id;
                            $cardid= substr($v['user_no'],2); 
                        }
                    }else{
	                          $s_id=$v['school_id'];
		                        $cardid=$v['user_no'];
	                  }			 
                    $sql1="SELECT card_no from zf_card_info where user_no = '".$cardid."' and school_id = ".$s_id;
                    $stmt0=$db->query($sql1);
                    $row0=$stmt0->fetch();
                    if(empty($row0)){
	                      $need[$k]['card_no']='';
                    }else{	
                        $need[$k]['card_no']=$row0['card_no'];
                    }
               }
               //把这些不在数据库中的结果插入进去
               $sql2="INSERT INTO zf_recharge_detail (card_no,credit,type,balance,pos_no,created_by,time,note,is_active,school_id,trade_no)"
                    . " values (:card_no,:credid,'weixinchongzhi','0','0','0',:time,'0','0',:school_id,:trade_no)";
               $stmt2=$db->prepare($sql2);
               foreach($need as $key => $value){             
                  $resul=$stmt2->execute(array(':card_no'=>$value['card_no'],':credid'=>$value['order_count'],':time'=>$value['order_time'],':school_id'=>$value['school_id'],':trade_no'=>$value['order_trade']));           
                  if($resul){
                        $stu_info=explode('|',$value['order_discount']);                     
                        $openid=$stu_info[0];
                        $stu_name=$stu_info[2];
                        $content="餐卡充值系统提醒!"."您已为学生".$stu_name."的卡号充值".$value['order_count']."元，谢谢您的使用！";
                        $title="充值成功";
                        if($value['school_id']==56650 || $value['school_id']==56651){
                            SendMsgs::sendSHMsgToPa($openid,$title,$content,$url="http://mobile.jxqwt.cn/information/index?openid=".$openid."&sid=56650",$picurl="");  
                        }else{
                            SendMsgs::sendSHMsgToPa($openid,$title,$content,$url="",$picurl=""); 
                        }
                         echo "Success! this id is ".$db->lastInsertId()."</br>";
                  }else{
                         echo "Fail!";
                  }
               }	
           }else{
               echo "No find result!</br>";
           }
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
      '56650'=>[
          'APPID' => 'wxc5c7e311f8d5d759',
          'MCHID' => '1366091602',
          'KEY' => 'yai1ga134d4t2q1kaife8i1it6ikwaqi',
          'SSLCERT_PATH' => 'apiclient_cert.pem',
          'SSLKEY_PATH' => 'apiclient_key.pem'
      ],
      '56744'=>[
           'APPID' => 'wx8c6755d40004036d',
           'MCHID' => '1480239942',
           'KEY' => 'ff4c0f4f0f3bae31cac8a75050c9c5f2',
           'SSLCERT_PATH' => 'apiclient_cert56651.pem',
           'SSLKEY_PATH' => 'apiclient_key56651.pem'
      ],
      '56758'=>[
         'APPID' => 'wx0e02770c9fc0f131',
         'MCHID' => '1487744682',
         'KEY' => 'WGSDYGJZXCWKBMC03758169608bmcbmc',
         'SSLCERT_PATH' => 'apiclient_cert56758.pem',
         'SSLKEY_PATH' => 'apiclient_key56758.pem'
      ]    
  ];
foreach($school_configs as $s_id=>$info)
{
	getresult($info['APPID'],$info['MCHID'],$info['KEY']);	
}
 


