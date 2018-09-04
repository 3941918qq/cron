<?php
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
// 通过DB的不再说明
function check_money($school_id,$card_no)
{
	$db = get_db();
	$stmt = $db->prepare("select * from zf_card_info where card_no = ? and school_id = ?");
	$stmt -> execute([$card_no,$school_id]);
	$result = $stmt->fetch();
	if($result)
	{
		$stmt1 = $db->prepare("select * from zf_recharge_detail where card_no = :id and type = 'weixinchongzhi' and is_active = 0 and school_id = :school_id order by id asc limit 1");
		$stmt1->execute([":id"=>$card_no,":school_id"=>$school_id]);
		$row = $stmt1 -> fetch();
		if($row)
		{
			$ret_money = $row['credit'] * 100 ;			
			$trade_no = $row['trade_no'];
			//return $ret_money * 100;
			return "$school_id\r\n$card_no\r\n$ret_money\r\n$trade_no";
		}else return "$school_id\r\n$card_no\r\n0\r\n".time()."TEMP";
	}else return "error";
}
// 通过DB的第二次请求到的数据进行查询判断
function check_moneyt($school_id,$card_no,$trade_no,$money)
{
	$db = get_db();
	$stmt = $db->prepare("select * from zf_card_info where card_no = ? and school_id = ?");
	$stmt -> execute([$card_no,$school_id]);
	$result = $stmt->fetch();
	if($result)
	{
		$stmt1 = $db->prepare("select * from zf_recharge_detail where card_no = :id and type = 'weixinchongzhi' and is_active = 0 and school_id = :school_id and trade_no = :trade_no order by id asc limit 1");
		$stmt1->execute([":id"=>$result['card_no'],":school_id"=>$school_id,":trade_no"=>$trade_no]);
		$row = $stmt1 -> fetch();
		if($row)
		{
			//$ret_money = $row['credit'] ;
			//$balance_money = $ret_money + number_format($money/100,2);
			$balance_money = number_format(intval($money)/100,2,'.','');
			$stmt2 = $db->prepare("update zf_recharge_detail set qctime = :time, is_active = 1, balance = :balance where id = :id and type = 'weixinchongzhi'");
			$stmt2->execute([":time" => time(),":balance"=>$balance_money,":id"=>$row['id']]);
			
			$stmt3 = $db->prepare("update zf_card_info set balance =  ? where id = ?");
			$stmt3->execute([$balance_money,$result['id']]);
			return true;
		}else return false;
	}else return false;
}
