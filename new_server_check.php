<?php
require_once __DIR__."/sendCardWeixin.class.php";
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

function main($info)
{
	$db = get_db();
	$count_arr = [7,8,13];
	$custome_arr = explode("\r\n", $info);
	$school_id = array_shift($custome_arr);
	global $sid;
	$sid=$school_id;
	if(strlen($school_id) != 5) return false;
	foreach ($count_arr as $_count) {
		foreach ($custome_arr as $row) {
			if(empty($row) ) continue;
			$detail_arr = explode(",", $row);
			if(count($detail_arr) != $_count )continue;
			if(!in_array(count($detail_arr), $count_arr)) continue;
			if(count($detail_arr) == 13)
			{
				if($detail_arr[4] == 2) insertRecharge($detail_arr,$db,$school_id);
				else if($detail_arr[4] == 1 || $detail_arr[4] == 5 ) insertDeal($detail_arr,$db,$school_id);
			}
			else if(count($detail_arr) == 8 )	insertRechargeLocal($detail_arr,$db,$school_id);
			else if(count($detail_arr) == 7) 	updateCardNo($detail_arr,$db,$school_id);
		}
	}
	return true;
}
function wx_push($card_no,$money,$db,$school_id,$balance,$createdtime,$pos_no)
{
	// return ;
        $stmt = $db->prepare("select * from zf_card_info where card_no = ? and school_id = ?");
        $stmt -> execute([$card_no,$school_id]);
        $result = $stmt->fetch();
		echo $createdtime;
        if($result)
        {
                //if( $result['balance'] == $balance + $money)
                //$db->exec("update zf_card_info set balance = $balance where card_no = $card_no");
                $post = [];
                $post['money'] = $money;
                $post['user_no'] = $result['user_no'];
				$post['balance'] = $balance;
				$post['name'] = $result['user_name'];
				$post['sid'] = $school_id;
				$post['time'] = $createdtime;
				$post['pos_no'] = $pos_no;
                $ret = sendWeiXin::sendCard($post);
				var_dump($ret);
        }

}


function insertDeal($arr,$db,$school_id)
{
	$amount = number_format($arr[6]/100,2,'.','');
	$balance = number_format($arr[7]/100,2,'.','');
	if($arr[4] == 5) $amount = 0 - $amount;
	$stmt = $db->prepare("insert ignore into zf_deal_detail (pos_sn,card_no,amount,balance,created,type,ser_no,school_id) values (:pos_no,:card_no,:amount,:balance,:created,:type,:ser_no,:school_id)");
        $stmt->execute([
                ":pos_no" => $arr[12],
                ":card_no" =>   $arr[0] ,
                ":amount" => $amount,
                ":balance" => $balance,
                ":created" => strtotime($arr[2]),
                ":type" => "shitangshuaka",
                ":ser_no" => $arr[11],
		":school_id"=>$school_id
        ]);
        updateCard($balance,$arr[0],$db,$school_id);

	$stmt = $db->prepare("select * from zf_card_info where card_no = ?  and school_id = ? ");
	$stmt->execute([$arr[0],$school_id]);
	$info = $stmt->fetch();
	
	if (strlen($info['user_no']) == 7) {
            $newuser_no = "T".$school_id.$info['user_no'];
        }elseif (strlen($info['user_no']) == 8) {
            $newuser_no = "53".$info['user_no'];
        }
	else $newuser_no = "T".$school_id.$info['user_no'];

	$ischool_db = sendWeiXin::getDB();
	$stmt = $ischool_db->prepare("select * from wp_ischool_student where stuno2 = ?");
	$stmt->execute([$newuser_no]);
	$infos = $stmt->fetch();
	$youxiaoqi = $infos['enddateck'];	//获取学生有效期时间戳
	$diff_time = time() - strtotime($arr[2]);
	if(intval($youxiaoqi) > intval(time()) &&  $diff_time < 3*60*60 ){
		wx_push($arr[0],$amount,$db,$school_id,$balance,strtotime($arr[2]),$arr[12]);
	}
}
function updateCard($balance,$cardno,$db,$school_id)
{
        $stmt1 = $db->prepare("update zf_card_info set balance = :balance where card_no = :card_no and school_id = :school_id");
        $stmt1->execute([":balance"=>$balance,":card_no"=>$cardno,":school_id"=>$school_id]);
}
function updateCardNo($arr,$db,$school_id)
{
	$stmt = $db->prepare("select * from zf_card_info where user_no = ? and school_id = ?");
	$stmt->execute([$arr[0],$school_id]);
	$info = $stmt->fetch();
	if($info)
	{
		if($arr[5] == 2) 
		{
			$status = 'guashi';
			$card_no = $arr[4];
			/*
			$stmt0 = $db->prepare("insert ignore into zf_guashi (card_no,user_no,user_name,role_id,school_id,department_id,created_by,created,phyid)values (:card_no,:user_no,:user_name,:role_id,:school_id,:department_id,:created_by,:created,:phyid)");
			$stmt0->execute([
				":card_no"=>$info['card_no'],
				":user_no"=>$info['user_no'],
				":user_name"=>$info['user_name'],
				":role_id"=>$info['role_id'],
				":school_id"=>$info['school_id'],
				":department_id"=>$info['department_id'],
				":created_by"=>$info['created_by'],
				":created"=>time(),
				":phyid"=>$info['phyid']
				]);
			*/

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
		try{
		$stmt1 = $db->prepare("update ignore zf_card_info set card_no = ?,status = ?,department_id = ?,user_name = ?,phyid=?,updated = ? where user_no = ? and school_id = ?");
		$stmt1->execute([$card_no,$status,$arr[2],$arr[1],$arr[3],time(),$arr[0],$school_id]);
		}catch(Exception $e)
		{
			print $e->getMessage(); 
			
		}
		
		if( $info['status'] == "guashi" && $status == "zhengchang" )
		{
			//insert into new card	
			$stmt1 = $db->prepare("insert ignore into zf_huanka (card_no,user_no,user_name,role_id,school_id,department_id,created_by,created,phyid)values (:card_no,:user_no,:user_name,:role_id,:school_id,:department_id,:created_by,:created,:phyid)");
                        $stmt1->execute([
                                ":card_no"=>$card_no,
                                ":user_no"=>$info['user_no'],
                                ":user_name"=>$info['user_name'],
                                ":role_id"=>$info['role_id'],
                                ":school_id"=>$info['school_id'],
                                ":department_id"=>$info['department_id'],
                                ":created_by"=>$info['created_by'],
                                ":created"=>time(),
                                ":phyid"=>$arr[3]
                                ]);

		}

	}else 
	{
		if(empty($arr[4])) return;
		$stmt1 = $db->prepare("insert ignore into zf_card_info (card_no,user_no,user_name,department_id,status,balance,role_id,phyid,school_id,created)values (:card_no,:user_no,:user_name,:department_id,:status,:balance,:role_id,:phyid,:school_id,:created)");
		$stmt1->execute(array(
			":card_no"=>$arr[4],
			":user_no"=>$arr[0],
			":user_name"=>$arr[1],
			":department_id"=>$arr[2],
			":status"=>"zhengchang",
			":balance"=>0,
			":role_id"=>0,
			":phyid"=>$arr[3],
			":school_id"=>$school_id,
			":created"=>time()
			));
	}

}
function insertRecharge($arr,$db,$school_id)
{
	//'100004','192.168.12.13','20170213162350',15,1,16600,600,16000,81953,57,0,'5208160910000254',3
	$amount = number_format($arr[6]/100,2,'.','');
	$balance = number_format($arr[7]/100,2,'.','');
	$stmt = $db->prepare("insert ignore into zf_recharge_detail (card_no,credit,type,balance,pos_no,created_by,time,is_active,ser_no,school_id) values (:card_no,:credit,:type,:balance,:pos_no,:created_by,:time,:is_active,:ser_no,:school_id)");
	$stmt -> execute([
		":card_no"=>$arr[0],
		":credit" => $amount,
		":type" => "xianjinchongzhi",
		":balance" => $balance,
		":pos_no" => $arr[12],
		":created_by" => 0,
		":time" => strtotime($arr[2]),
		":is_active" => 1,
		":ser_no" => $arr[11],
		":school_id"=>$school_id
		]);
	updateCard($balance,$arr[0],$db,$school_id);
}
function insertRechargeLocal($arr,$db,$school_id)
{
	$stmt0 = $db->prepare("select * from zf_card_info where user_no = ? and school_id = ?");
	$stmt0->execute([$arr[7],$school_id]);
	$info = $stmt0->fetch();
	if($info)
	{
		$type = "xianjinchongzhi";
		if($arr[5] == "x") $credit = intval($arr[3]);
		else {
			$credit = 0 - intval($arr[3]);
			$type = "tuikuan";
		}
		$stmt = $db->prepare("insert ignore into zf_recharge_detail (card_no,credit,type,balance,pos_no,created_by,time,is_active,ser_no,school_id) values (:card_no,:credit,:type,:balance,:pos_no,:created_by,:time,:is_active,:ser_no,:school_id)");
		$stmt -> execute([
			":card_no"=>$info['card_no'],
			":credit" => $credit,
			":type" => $type,
			":balance" => $arr[4],
			":pos_no" => "0",
			":created_by" => 0,
			":time" => strtotime($arr[1]),
			":is_active" => 1,
			":ser_no" => 2,
			":school_id"=>$school_id
		]);
		updateCard($arr[4],$arr[0],$db,$school_id);
	}

}
