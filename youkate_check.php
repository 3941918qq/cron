<?php
require_once __DIR__ . "/sendCardWeixin.class.php";
function getDb() {
	$pdo = new PDO("mysql:host=127.0.0.1;dbname=card1", "root", 'hnzf123456', array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
	return $pdo;
}
function getDb1() {
        $pdo = new PDO("mysql:host=127.0.0.1;dbname=card_water", "root", 'hnzf123456', array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
}
function insertWaterRecharge($arr, $school_id) {
        global $db1;
        if(trim($arr[5]) == "微信充值") return ;
        $stmt = $db1->prepare("insert ignore into zf_recharge_detail (card_no,credit,type,balance,pos_no,created_by,time,is_active,ser_no,school_id) values (:card_no,:credit,:type,:balance,:pos_no,:created_by,:time,:is_active,:ser_no,:school_id)");
        $stmt->execute([
                ":card_no" => trim($arr[0]),
                ":credit" => trim($arr[2]),
                ":type" => "xianjinchongzhi",
                ":balance" => trim($arr[8]),
                ":pos_no" => trim($arr[1]),
                ":created_by" => 0,
                ":time" => strtotime($arr[4]),
                ":is_active" => 1,
                ":ser_no" => 0, 
                ":school_id" => $school_id,
        ]);
        //当前情况下优先保证卡里面的金额的正确性
        //因为金额不能持续
        updateCard(trim($arr[8]), trim($arr[0]), $school_id);

}
function insertRecharge($arr, $school_id) {
	global $db;
	if(trim($arr[5]) == "微信充值") return ;
	$stmt = $db->prepare("insert ignore into zf_recharge_detail (card_no,credit,type,balance,pos_no,created_by,time,is_active,ser_no,school_id) values (:card_no,:credit,:type,:balance,:pos_no,:created_by,:time,:is_active,:ser_no,:school_id)");
	$stmt->execute([
		":card_no" => trim($arr[0]),
		":credit" => trim($arr[2]),
		":type" => "xianjinchongzhi",
		":balance" => trim($arr[8]),
		":pos_no" => trim($arr[1]),
		":created_by" => 0,
		":time" => strtotime($arr[4]),
		":is_active" => 1,
		":ser_no" => 0,
		":school_id" => $school_id,
	]);
	//当前情况下优先保证卡里面的金额的正确性
	//因为金额不能持续
	updateCard(trim($arr[8]), trim($arr[0]), $school_id);

}
function insertDeal($arr, $school_id) {
	global $db;
	$stmt = $db->prepare("insert ignore into zf_deal_detail (pos_sn,card_no,amount,balance,created,type,ser_no,school_id) values (:pos_no,:card_no,:amount,:balance,:created,:type,:ser_no,:school_id)");
	$stmt->execute([
		":pos_no" => trim($arr[1]),
		":card_no" => trim($arr[0]),
		":amount" => trim($arr[2]),
		":balance" => trim($arr[8]),
		":created" => strtotime($arr[4]),
		":type" => "shitangshuaka",
		":ser_no" => 0,
		":school_id" => $school_id,
	]);
	//采集顺序性保证卡里面的金额的正确性
	updateCard(trim($arr[8]), trim($arr[0]), $school_id);
	// weixin chongzhi
	$stmt1 = $db->prepare("select * from zf_card_info where card_no = ?  and school_id = ? ");
        $stmt1->execute([trim($arr[0]), $school_id]);
        $info = $stmt1->fetch();
	
	if(!$info) return;
	$newuser_no = $info['user_no'];

        $ischool_db = sendWeiXin::getDB();
        $stmt2 = $ischool_db->prepare("select * from wp_ischool_student where stuno2 = ?");
        $stmt2->execute([$newuser_no]);
        $infos = $stmt2->fetch();
        $youxiaoqi = $infos['enddateck'] ? $infos['enddateck'] : 0; //获取学生有效期时间戳
        $diff_time = time() - strtotime($arr[4]);
        if (intval($youxiaoqi) > intval(time()) && $diff_time < 3 * 60 * 60) {
                $post = [];
                $post['money'] = trim($arr[2]);
                $post['user_no'] = $newuser_no;
                $post['balance'] = trim($arr[8]);
                $post['name'] = $info['user_name'];
                $post['sid'] = $school_id;
                $post['time'] = strtotime($arr[4]);
                $post['pos_no'] = $arr[1];
                //if($school_id != 56650 ){
                if ($post['sid'] == 56651) {
                        $post['sid'] = 56650;
                }
                $ret = sendWeiXin::sendCard($post);
                var_dump($ret);
        }


}

function insertWaterDeal($arr, $school_id) {
        global $db1;
        $stmt = $db1->prepare("insert ignore into zf_deal_detail (pos_sn,card_no,amount,balance,created,type,ser_no,school_id) values (:pos_no,:card_no,:amount,:balance,:created,:type,:ser_no,:school_id)");
        $stmt->execute([
                ":pos_no" => trim($arr[1]),
                ":card_no" => trim($arr[0]),
                ":amount" => trim($arr[2]),
                ":balance" => trim($arr[8]),
                ":created" => strtotime($arr[4]),
                ":type" => "shitangshuaka",
                ":ser_no" => 0,
                ":school_id" => $school_id,
        ]);
        //采集顺序性保证卡里面的金额的正确性
        updateWaterCard(trim($arr[8]), trim($arr[0]), $school_id);
        // weixin chongzhi
	/*
        $stmt1 = $db->prepare("select * from zf_card_info where card_no = ?  and school_id = ? ");
        $stmt1->execute([trim($arr[0]), $school_id]);
        $info = $stmt1->fetch();

        if(!$info) return;
        $newuser_no = $info['user_no'];

        $ischool_db = sendWeiXin::getDB();
        $stmt2 = $ischool_db->prepare("select * from wp_ischool_student where stuno2 = ?");
        $stmt2->execute([$newuser_no]);
        $infos = $stmt2->fetch();
        $youxiaoqi = $infos['enddateck'] ? $infos['enddateck'] : 0; //获取学生有效期时间戳
        $diff_time = time() - strtotime($arr[4]);
        if (intval($youxiaoqi) > intval(time()) && $diff_time < 3 * 60 * 60) {
                $post = [];
                $post['money'] = trim($arr[2]);
                $post['user_no'] = $newuser_no;
                $post['balance'] = trim($arr[8]);
                $post['name'] = $info['user_name'];
                $post['sid'] = $school_id;
                $post['time'] = strtotime($arr[4]);
                $post['pos_no'] = $arr[1];
                //if($school_id != 56650 ){
                if ($post['sid'] == 56651) {
                        $post['sid'] = 56650;
                }
                $ret = sendWeiXin::sendCard($post);
                var_dump($ret);
        }
	*/

}

function updateWaterInfo($arr,$school_id)
{
        global $db1;
        $stmt1 = $db1->prepare("select * from zf_card_info where user_no = ? and school_id = ?");
        $stmt1->execute([$arr[0], $school_id]);
        $ret = $stmt1->fetch();
        if (empty($ret)) {
                $stmt2 = $db1->prepare("insert ignore into zf_card_info (card_no,user_no,user_name,department_id,status,balance,role_id,phyid,school_id,created)values (:card_no,:user_no,:user_name,:department_id,:status,:balance,:role_id,:phyid,:school_id,:created)");
                $stmt2->execute(array(
                        ":card_no" => $arr[2],
                        ":user_no" => $arr[0],
                        ":user_name" => $arr[1],
                        ":department_id" => 0,
                        ":status" => "zhengchang",
                        ":balance" => $arr[3],
                        ":role_id" => 0,
                        ":phyid" => 0,
                        ":school_id" => $school_id,
                        ":created" => time(),
                ));
        } else {
                try {
                        $stmt2 = $db1->prepare("update ignore zf_card_info set card_no = ?,status = ?,user_name = ?,phyid=?,updated = ? where user_no = ? and school_id = ?");
                        $stmt2->execute([$arr[2], "zhengchang", $arr[1], 0, time(), $arr[0], $school_id]);
                        // 
			/*
                        if($arr[2] != $ret['card_no'])
                        {
                                //更新CARD表里面的数据，停止EPC和电话卡
                        }
			*/
                } catch (Exception $e) {
                        print $e->getMessage();

                }

        }

}
function updateCard($balance, $cardno, $school_id) {
	global $db;
        $stmt1 = $db->prepare("update zf_card_info set balance = :balance where card_no = :card_no and school_id = :school_id");
        $stmt1->execute([":balance" => $balance, ":card_no" => $cardno, ":school_id" => $school_id]);
}
function updateWaterCard($balance, $cardno, $school_id) {
        global $db1;
        $stmt1 = $db1->prepare("update zf_card_info set balance = :balance where card_no = :card_no and school_id = :school_id");
        $stmt1->execute([":balance" => $balance, ":card_no" => $cardno, ":school_id" => $school_id]);
}

function updateOrCreate($arr, $school_id) {
	global $db;
	
	if (strlen($arr[4]) == 8) {
		$info_arr = str_split($arr[4], 2);
		$info_arr_final = array_reverse($info_arr);
		$info_num = join("", $info_arr_final);
		$arr[4] = hexdec($info_num);
	}

	$stmt1 = $db->prepare("select * from zf_card_info where user_no = ? and school_id = ?");
	$stmt1->execute([$arr[0], $school_id]);
	$ret = $stmt1->fetch();
	if (empty($ret)) {
		$stmt2 = $db->prepare("insert ignore into zf_card_info (card_no,user_no,user_name,department_id,status,balance,role_id,phyid,school_id,created)values (:card_no,:user_no,:user_name,:department_id,:status,:balance,:role_id,:phyid,:school_id,:created)");
		$stmt2->execute(array(
			":card_no" => $arr[2],
			":user_no" => $arr[0],
			":user_name" => $arr[1],
			":department_id" => 0,
			":status" => "zhengchang",
			":balance" => $arr[3],
			":role_id" => 0,
			":phyid" => $arr[4],
			":school_id" => $school_id,
			":created" => time(),
		));
	} else {
		try {
			$stmt2 = $db->prepare("update ignore zf_card_info set card_no = ?,status = ?,user_name = ?,phyid=?,updated = ? where user_no = ? and school_id = ?");
			$stmt2->execute([$arr[2], "zhengchang", $arr[1], $arr[4], time(), $arr[0], $school_id]);
			// 
			if($arr[2] != $ret['card_no'])
			{
				//更新CARD表里面的数据，停止EPC和电话卡
			}
		} catch (Exception $e) {
			print $e->getMessage();

		}

	}
}

function main($info) {
	$array = explode("\r\n", $info);
	$school_id = array_shift($array);
	if (strlen($school_id) != 5) {
		return false;
	}

	foreach ($array as $row) {
		if(empty($row)) continue;
		$row_arr = explode(",", $row);
		if (count($row_arr) == 10) {
			if (trim($row_arr[7]) == "消费") {
				insertDeal($row_arr, $school_id);
			} else {
				insertRecharge($row_arr, $school_id);
			}
		}
		else if (count($row_arr) == 11) {
                        if (trim($row_arr[7]) == "消费") {
                                insertWaterDeal($row_arr, $school_id);
                        } else {
                                insertWaterRecharge($row_arr, $school_id);
                        }

		} else if (count($row_arr) == 5) {
			updateOrCreate($row_arr, $school_id);
		} else if (count($row_arr) == 4) {
			updateWaterInfo($row_arr,$school_id);
		} else {
			continue;
		}
	}
	return true;
}

$conn_args = array(
        'host' => '127.0.0.1',
        'port' => 5672,
        'login' => 'guest',
        'password' => 'hnzf55030687',
        'vhost' => '/',
);
$e_name = 'newcard';
$q_name = 'newcard';
$k_route = 'newcard';

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
$db = getDb();
$db1=getDb1();
while (true) {
        $q->consume('callback');
        $channel->qos(0, 1);
}
$conn->disconnect();

function callback($envelope, $queue) {
        $msg = $envelope->getBody();
        $msg = trim($msg);
        $result = main($msg);
        $queue->ack($envelope->getDeliveryTag());
}












