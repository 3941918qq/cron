<?php
require_once __DIR__ . "/sendCardWeixin.class.php";
function get_db() {
	$pdo = new PDO(
		'mysql:host=localhost;dbname=card',
		'root',
		'hnzf123456',
		array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'")
	);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
	return $pdo;
}
function get_ischooldb() {
	$pdo = new PDO(
		'mysql:host=localhost;dbname=ischool',
		'root',
		'hnzf123456',
		array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'")
	);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
	return $pdo;
}
function main($info, $db) {
	$count_arr = [7, 8, 13];
	$custome_arr = explode("\r\n", $info);
	$school_id = array_shift($custome_arr);
	if (strlen($school_id) != 5) {
		return false;
	}
	
	foreach ($custome_arr as $row) {
		if (empty($row)) {
			continue;
		}

		$detail_arr = explode(",", $row);
		/*
		if (count($detail_arr) != $_count) {
			continue;
		}
		*/
		if (!in_array(count($detail_arr), $count_arr)) {
			continue;
		}

		if (count($detail_arr) == 13) {
			if ($detail_arr[4] == 2) {
				insertRecharge($detail_arr, $db, $school_id);
			} else if ($detail_arr[4] == 1 || $detail_arr[4] == 5) {
				insertDeal($detail_arr, $db, $school_id);
			}

		} else if (count($detail_arr) == 8) {
			insertRechargeLocal($detail_arr, $db, $school_id);
		} else if (count($detail_arr) == 7) {
			updateCardNo($detail_arr, $db, $school_id);
		}

	}
	return true;
}
function wx_push($card_no, $money, $db, $school_id, $balance, $time) {
	$stmt = $db->prepare("select * from zf_card_info where card_no = ? and school_id = ?");
	$stmt->execute([$card_no, $school_id]);
	$result = $stmt->fetch();
	if ($result) {
		if($school_id == 56651) $school_id = 56650;
		$post = [];
		$post['money'] = $money;
		$post['user_no'] = $result['user_no'];
		$post['balance'] = $balance;
		$post['name'] = $result['user_name'];
		$post['sid'] = $school_id;
		$post['time'] = $time;
		$ret = sendWeiXin::sendCard($post);
		var_dump($ret);
	}

}

function insertDeal($arr, $db, $school_id) {
	$amount = number_format($arr[6] / 100, 2, '.', '');
	$balance = number_format($arr[7] / 100, 2, '.', '');
	if ($arr[4] == 5) {
		$amount = 0 - $amount;
	}

	try {
		$stmt = $db->prepare("insert ignore into zf_deal_detail (pos_sn,card_no,amount,balance,created,type,ser_no,school_id) values (:pos_no,:card_no,:amount,:balance,:created,:type,:ser_no,:school_id)");
		$stmt->execute([
			":pos_no" => $arr[12],
			":card_no" => $arr[0],
			":amount" => $amount,
			":balance" => $balance,
			":created" => strtotime($arr[2]),
			":type" => "shitangshuaka",
			":ser_no" => $arr[11],
			":school_id" => $school_id,
		]);
	} catch (Exception $e) {
                print $e->getMessage();
		return ;

        }
	updateCard($balance, $arr[0], $db, $school_id);
	$stmt = $db->prepare("select * from zf_card_info where card_no = ?  and school_id = ? ");
	$stmt->execute([$arr[0], $school_id]);
	$info = $stmt->fetch();
	//$user_no = $info['user_no'];
	//if ($school_id == 56651) $school_id = 56650;
	if (strlen($info['user_no']) == 7) {
		$newuser_no = "T" . $school_id . $info['user_no'];
	} elseif (strlen($info['user_no']) == 8) {
		$newuser_no = "53" . $info['user_no'];
	}
	else $newuser_no = "T" . $school_id . $info['user_no'];

	$ischool_db = sendWeiXin::getDB();
	$stmt = $ischool_db->prepare("select * from wp_ischool_student where stuno2 = ?");
	$stmt->execute([$newuser_no]);
	$infos = $stmt->fetch();
	$youxiaoqi = $infos['enddateck']?$infos['enddateck']:0; //获取学生有效期时间戳
	$diff_time = time() - strtotime($arr[2]);
	if (intval($youxiaoqi) > intval(time())  &&  $diff_time < 3*60*60     ) {
		//wx_push($arr[0], $amount, $db, $school_id, $balance, strtotime($arr[2]));
                $post = [];
                $post['money'] = $amount;
                $post['user_no'] = $newuser_no;
                $post['balance'] = $balance;
                $post['name'] = $info['user_name'];
                $post['sid'] = $school_id;
                $post['time'] = strtotime($arr[2]);
		$post['pos_no'] = $arr[12];
		//if($school_id != 56650 ){
		if($post['sid'] == 56651) $post['sid'] = 56650;
                $ret = sendWeiXin::sendCard($post);
		var_dump($ret);
		//}

	}
}
function updateCard($balance, $cardno, $db, $school_id) {
	$stmt1 = $db->prepare("update zf_card_info set balance = :balance where card_no = :card_no and school_id = :school_id");
	$stmt1->execute([":balance" => $balance, ":card_no" => $cardno, ":school_id" => $school_id]);
}
function updateCardNo($arr, $db, $school_id) {
	$stmt = $db->prepare("select * from zf_card_info where user_no = ? and school_id = ?");
	$stmt->execute([$arr[0],$school_id]);
	$info = $stmt->fetch();
	if ($info) {
		if($arr[5] == 2)
                {
                        $status = 'guashi';
                        $card_no = $arr[4];
                }
                else if ($arr[5] != "" && $arr[5] == 0 )
                {
                        $status = 'zhengchang';
                        $card_no = $arr[4];
                }
                else {
                        $status = 'zhuxiao';
                        $card_no = $info['card_no'];
                }
		try {
			$stmt1 = $db->prepare("update ignore zf_card_info set card_no = ?,status = ?,department_id = ?,user_name = ?,phyid=?,updated = ? where user_no = ? and school_id = ?");
			$stmt1->execute([$card_no, $status, $arr[2], $arr[1], $arr[3], time(), $arr[0], $school_id]);
		} catch (Exception $e) {
			print $e->getMessage();

		}

	} else {
		if (empty($arr[4])) {
			return;
		}

		//INSERT INTO THE WPISCHOOL
		$stmt1 = $db->prepare("insert ignore into zf_card_info (card_no,user_no,user_name,department_id,status,balance,role_id,phyid,school_id,created)values (:card_no,:user_no,:user_name,:department_id,:status,:balance,:role_id,:phyid,:school_id,:created)");
		$stmt1->execute(array(
			":card_no" => $arr[4],
			":user_no" => $arr[0],
			":user_name" => $arr[1],
			":department_id" => $arr[2],
			":status" => "zhengchang",
			":balance" => 0,
			":role_id" => 0,
			":phyid" => $arr[3],
			":school_id" => $school_id,
			":created" => time(),
		));
                //同步更新ischool库的电话卡号和EPC号
                $dbh=get_ischooldb();
                if (strlen($arr[0]) == 7) {
                        $stuno2 = "T" . $school_id . $arr[0];
                } elseif (strlen($arr[0]) == 8) {
                        $stuno2 = "53" . $arr[0];
                }
                //查询卡库epc和电话卡
                $sqlk="select * from wp_ischool_kaku where stuno2 = '".$stuno2."'";
                $stmt=$dbh->query($sqlk);
                if( $stmt->rowCount()){
                    //更新student表的epc号
                   $row=$stmt->fetch(); 
                   $sqls="select * from wp_ischool_student where stuno2 = '".$stuno2."'";
                   $stmts=$dbh->query($sqls);
                   if($stmts->rowCount()){  
                      $stu=$stmts->fetch(); 
                      $stmtss= $dbh->prepare("update wp_ischool_student set cardid = ? where stuno2 = ? ");
                      $stmtss->execute(array($row['epc'],$stuno2));
                      //根据学生id更新card表电话卡
                      $sqlc="select * from wp_ischool_student_card where stu_id = ".$stu['id'];
                      $stmtc=$dbh->query($sqlc);
                      if($stmtc->rowCount()){
                          $stmtcc=$dbh->prepare("update wp_ischool_student_card set card_no = ? where stu_id = ? ");
                          $stmtcc->execute(array($row['telid'],$stu['id']));
                      }else{
                          $stmtcc=$dbh->prepare("insert into  wp_ischool_student_card (stu_id,card_no,flag,ctime) values (:stu_id,:card_no,1,:ctime) ");
                          $stmtcc->execute(array(
                              ":stu_id"=>$stu['id'],
                              ":card_no"=>$row['telid'],
                              ":ctime"=>time(),
                          ));
                      }
                   }
                   echo "ok";
                   
                }

                   
	}

}
$arr=['9999999','张校良',1,'1111111111','1314'];
$db = get_db();
updateCardNo($arr,$db,56744);
function insertRecharge($arr, $db, $school_id) {
	$amount = number_format($arr[6] / 100, 2, '.', '');
	$balance = number_format($arr[7] / 100, 2, '.', '');
	$stmt = $db->prepare("insert ignore into zf_recharge_detail (card_no,credit,type,balance,pos_no,created_by,time,is_active,ser_no,school_id) values (:card_no,:credit,:type,:balance,:pos_no,:created_by,:time,:is_active,:ser_no,:school_id)");
	$stmt->execute([
		":card_no" => $arr[0],
		":credit" => $amount,
		":type" => "xianjinchongzhi",
		":balance" => $balance,
		":pos_no" => $arr[12],
		":created_by" => 0,
		":time" => strtotime($arr[2]),
		":is_active" => 1,
		":ser_no" => $arr[11],
		":school_id" => $school_id,
	]);
	updateCard($balance, $arr[0], $db, $school_id);
}
function insertRechargeLocal($arr, $db, $school_id) {
	$stmt0 = $db->prepare("select * from zf_card_info where user_no = ? and school_id = ?");
	$stmt0->execute([$arr[7],$school_id]);
	$info = $stmt0->fetch();
	if ($info) {
		$type = "xianjinchongzhi";
		if ($arr[5] == "x") {
			$credit = intval($arr[3]);
		} else {
			$credit = 0 - intval($arr[3]);
			$type = "tuikuan";
		}
		$stmt = $db->prepare("insert ignore into zf_recharge_detail (card_no,credit,type,balance,pos_no,created_by,time,is_active,ser_no,school_id) values (:card_no,:credit,:type,:balance,:pos_no,:created_by,:time,:is_active,:ser_no,:school_id)");
		$stmt->execute([
			":card_no" => $info['card_no'],
			":credit" => $credit,
			":type" => $type,
			":balance" => $arr[4],
			":pos_no" => "0",
			":created_by" => 0,
			":time" => strtotime($arr[1]),
			":is_active" => 1,
			":ser_no" => 2,
			":school_id" => $school_id,
		]);
		updateCard($arr[4], $arr[0], $db, $school_id);
	}

}

$conn_args = array(
	'host' => '127.0.0.1',
	'port' => 5672,
	'login' => 'guest',
	'password' => 'hnzf55030687',
	'vhost' => '/',
);
$e_name = 'card';
$q_name = 'card';
$k_route = 'card';

$conn = new AMQPConnection($conn_args);
if (!$conn->connect()) {
	die('Cannot connect to the broker');
}
$channel = new AMQPChannel($conn);
$ex = new AMQPExchange($channel);
$ex->setName($e_name);
$ex->setType(AMQP_EX_TYPE_DIRECT);
$ex->setFlags(AMQP_DURABLE);

$q = new AMQPQueue($channel);
$q->setName($q_name);
$q->setFlags(AMQP_DURABLE); //持久化 
$q->declareQueue();
$q->bind($e_name, $k_route);
//$db = get_db();
while (true) {
	//$arr = $q->get();
	/*
	if ($arr) {
		$res = $q->ack($arr->getDeliveryTag());
		$info = $arr->getBody();
		main($info, $db);
	}*/
    $q->consume('callback');
    /**
     * 我们可以使用$channel->qos();方法，并设置prefetch_count=1。
     * 这样是告诉RabbitMQ，再同一时刻，不要发送超过1条消息给一个工作者（worker），
     * 直到它已经处理了上一条消息并且作出了响应。这样，RabbitMQ就会把消息分发给下一个空闲的工作者（worker）
     */
    $channel->qos(0, 1);
}
$conn->disconnect();

function callback($envelope, $queue) 
{
    global $db;
    $msg = $envelope->getBody();
    $msg = trim($msg);
    $result =  main($msg, $db);
    //$queue->ack()。当工作者（worker）完成了任务，就发送一个响应。
    //当工作者（worker）挂掉这后，所有没有响应的消息都会重新发送。
    $queue->ack($envelope->getDeliveryTag());
}
