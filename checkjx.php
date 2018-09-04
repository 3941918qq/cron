<?php
include_once("/data/web/ischool/mobile/web/pay/WxPayPubHelper.php");
include_once("/data/web/ischool/mobile/web/pay/SendMsgs.php");
function getresult($appid,$mchid,$key){
           //对账单日期
        $bill_date = date("Ymd",time()-86400);
          
         //使用对账单接口
        $downloadBill = new DownloadBill_pub();
        $downloadBill->setParameter("bill_date","$bill_date");//对账单日期 
        $downloadBill->setParameter("bill_type","ALL");//账单类型 

         //对账单接口结果
           $downloadBillResult= $downloadBill->getResult($appid,$mchid,$key);
           $code=$downloadBillResult['return_code'];

         
        if ($code == "FAIL") {
             echo "通信出错：".$downloadBillResult['return_msg'];
        }else{
            
	     $res=deal_wechat_return_result($downloadBill->response);
             //取昨天0时以后的jx数据
              $ctime=strtotime(date("Y-m-d"))-86400;
              $pdo=get_db();
               //和对账单中的订单号做比对如果,如果存在，并且zfopenid为空，那么及时更新
              // var_dump($res);
              foreach($res  as $k=>$v){
                    $stmt='';
                    $sql="SELECT * FROM wp_ischool_orderjx where trade_no=".$v['order_trade']." and ctime > ".$ctime." and zfopenid is null";
                    $stmt=$pdo->query($sql);
                    $uid=$stmt->fetch()['uid'];
                    if($uid){                    
		                  	//var_dump($v['order_transid']);
                        $result=explode('|',$v['order_discount']);
                        $xqxn = explode('-',$result[6]); 
                        $pdxq =$xqxn[4]; 
                        $num = 0;                    
                         if ("yxqi" == $pdxq) {
                            $num = "+6 month";
                         } else if ("yxni" == $pdxq) {
                            $num = "+12 month";
                         } else if ("ygyu" == $pdxq) {
                            $num = "+1 month";
                         }
                        $end = array();
                        $end['pa'] = (($xqxn[0] == "pa") ? $num : 0);
                        $end["jx"] = (($xqxn[1] == "jx") ? $num : 0);
                        $end["qq"] = (($xqxn[2] == "qq") ? $num : 0);
                        $end["ck"] = (($xqxn[3] == "ck") ? $num : 0);
                        $sql_student = "select enddatepa,enddatejx,enddateqq,enddateck,upendtimepa,upendtimejx,upendtimeqq,upendtimeck from wp_ischool_student where id=". $result[3];
                        $old_end=$pdo->query($sql_student);
                        $old_enddate = $old_end->fetchAll();
			$old_enddate[0]['enddatepa']= ($old_enddate[0]['enddatepa']==NULL)? 0:$old_enddate[0]['enddatepa'];
                        $old_enddate[0]['enddatejx']= ($old_enddate[0]['enddatejx']==NULL)? 0:$old_enddate[0]['enddatejx'];
                        $old_enddate[0]['enddateqq']= ($old_enddate[0]['enddateqq']==NULL)? 0:$old_enddate[0]['enddateqq'];                   
                        $old_enddate[0]['enddateck']= ($old_enddate[0]['enddateck']==NULL)? 0:$old_enddate[0]['enddateck'];                      
                        $old_enddate[0]['upendtimepa']=($old_enddate[0]['upendtimepa']==NULL)? 0:$old_enddate[0]['upendtimepa'];             
                        $old_enddate[0]['upendtimejx']=($old_enddate[0]['upendtimejx']==NULL)? 0:$old_enddate[0]['upendtimejx'];
                        $old_enddate[0]['upendtimeqq']=($old_enddate[0]['upendtimeqq']==NULL)? 0:$old_enddate[0]['upendtimeqq'];                                   
                        $old_enddate[0]['upendtimeck']=($old_enddate[0]['upendtimeck']==NULL)? 0:$old_enddate[0]['upendtimeck'];
		        //判断时间如何更新
                        $enddatepa = ($end['pa'] == 0) ? $old_enddate[0]['enddatepa']: ((!$old_enddate || $old_enddate[0]['enddatepa'] < time())?strtotime($end['pa']):strtotime($end['pa'],$old_enddate[0]['enddatepa']));//有效期的时间
                        $enddatejx = ($end['jx'] == 0) ? $old_enddate[0]['enddatejx']: ((!$old_enddate || $old_enddate[0]['enddatejx'] < time())?strtotime($end['jx']):strtotime($end['jx'],$old_enddate[0]['enddatejx']));
                        $enddateqq = ($end['qq'] == 0) ? $old_enddate[0]['enddateqq']: ((!$old_enddate || $old_enddate[0]['enddateqq'] < time())?strtotime($end['qq']):strtotime($end['qq'],$old_enddate[0]['enddateqq']));
                        $enddateck = ($end['ck'] == 0) ? $old_enddate[0]['enddateck']: ((!$old_enddate || $old_enddate[0]['enddateck'] < time())?strtotime($end['ck']):strtotime($end['ck'],$old_enddate[0]['enddateck']));
                        $untimepa = ($end['pa'] == 0) ? $old_enddate[0]['upendtimepa']:time(); //更新有效期的时间
                        $untimejx = ($end['jx'] == 0) ? $old_enddate[0]['upendtimejx']:time();
                        $untimeqq = ($end['qq'] == 0) ? $old_enddate[0]['upendtimeqq']:time();
                        $untimeck = ($end['ck'] == 0) ? $old_enddate[0]['upendtimeck']:time();
                        //三高走截止日期型
                        switch($pdxq){
                          case 'oneyear':
                            $enddatejx=$enddateqq=$enddatepa=$enddateck=1535644800;
                            break;
                          case 'twoyear':
                            $enddatejx=$enddateqq=$enddatepa=$enddateck=1567180800;
                            break;
                          case 'threeyear':
                            $enddatejx=$enddateqq=$enddatepa=$enddateck=1598803200;
                            break;
                        }
                   
                        $up_student_sql = "update wp_ischool_student set enddatepa=".$enddatepa.",enddatejx=".$enddatejx.", enddateqq=".$enddateqq.", enddateck=".$enddateck.",upendtimepa=".$untimepa.",upendtimejx=".$untimejx.",upendtimeqq=".$untimeqq.",upendtimeck=".$untimeck." where id=".$result[3];
                       
                        if($pdo->exec($up_student_sql))
                        {
                            $ispa = (($xqxn[0] == "pa") ? 1 : 0); //根据支付传参确定是给哪一项产品支付
                            $isjx = (($xqxn[1] == "jx") ? 1 : 0);
                            $isqq = (($xqxn[2] == "qq") ? 1 : 0);
                            $isck = (($xqxn[3] == "ck") ? 1 : 0);
                            $up_order_sql = "update wp_ischool_orderjx set ispasspa=".$ispa.",ispassjx=".$isjx.",ispassqq=".$isqq.",ispassck=".$isck.",utime=".time().",zfopenid='".$result[0]."',trans_id=".$v['order_transid'].",zfuid=".$uid." where stuid=".$result[3]." and trade_no=".$result[4]." and ispass=0";
                           
                            $up_order_sql =$pdo->exec($up_order_sql);
                              // var_dump($up_order_sql);
                            // $syx = $mysql->affected_rows; //记录影响行数
                            $openid = $result[0];
                            if($up_order_sql){
                                $conpa = ($xqxn[0] == "pa") ? "平安通知有效期更新至".date("Y年m月d日",$enddatepa)."。": "";
                                $conjx = ($xqxn[1] == "jx") ? "家校沟通有效期更新至".date("Y年m月d日",$enddatejx)."。": "";
                                $conqq = ($xqxn[2] == "qq") ? "亲情电话有效期更新至".date("Y年m月d日",$enddateqq)."。": "";
                                $conck = ($xqxn[3] == "ck") ? "餐卡微信充值有效期更新至".date("Y年m月d日",$enddateck)."。": "";
                                $content = "尊敬的家长您好!"."您已为学生".$result[2]."缴费".$result[5]."元，".$conpa.$conjx.$conqq.$conck;
                                $title="开通成功";
                                // var_dump($openid);
                                if($result[1]==56650){
                                    SendMsgs::sendSHMsgToPa($openid,$title,$content,$url="http://mobile.jxqwt.cn/information/index?openid=".$openid."&sid=56650",$picurl="");  
                                }else{
                                    SendMsgs::sendSHMsgToPa($openid,$title,$content,$url="",$picurl=""); 
                                }
                                echo $result[2]."开通成功！";
                            }

                        }
                    }
		

              }
   	  
	    $pdo=null;
	   
        }
    
 }
function get_db()
{
    $pdo = new PDO(
            'mysql:host=localhost;dbname=ischool',
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
        $result[trim($reponse[$base_index + 7])] = array(
	    'order_trade'=>trim($reponse[$base_index + 7]),
            'order_time' => strtotime(trim($reponse[$base_index + 1])),
            'order_count' => trim($reponse[$base_index + 13]),
            'order_discount' => trim($reponse[$base_index + 22]),
	    'order_transid' => trim($reponse[$base_index + 6])
        );
    }
    return $result;
}
 //获取账户信息
$school_configs=[
      '56744'=>[
           'APPID' => 'wx8c6755d40004036d',
           'MCHID' => '1480239942',
           'KEY' => 'ff4c0f4f0f3bae31cac8a75050c9c5f2',
           'SSLCERT_PATH' => 'apiclient_cert56651.pem',
           'SSLKEY_PATH' => 'apiclient_key56651.pem'
      ],
  ];
foreach($school_configs as $s_id=>$info)
{
	getresult($info['APPID'],$info['MCHID'],$info['KEY']);	
}
 



