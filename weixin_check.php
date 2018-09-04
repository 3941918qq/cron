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
function check_money($str,$school_id,$money,$card_id)
{
	//g_user_name + ":" + g_card_no + ":" + money
	//$arr = explode(":", $str);
	//if(count($arr) != 3) return 0;
	//if( intval($arr[2]) > 1000000 ) return 0;
	$db = get_db();

	$stmt = $db->prepare("select * from zf_card_info where user_no = ? and school_id = ? and card_no = ?");
	$stmt -> execute([$str,$school_id,$card_id]);
	$result = $stmt->fetch();
	if($result)
	{
		$stmt1 = $db->prepare("select * from zf_recharge_detail where card_no = :id and (type = 'weixinchongzhi' or type='ZFBJSAPI' or type='WXAPPJSAPI') and is_active = 0 and school_id = :school_id order by id asc limit 1");
		$stmt1->execute([":id"=>$result['card_no'],":school_id"=>$school_id]);
		$row = $stmt1 -> fetch();
		if($row)
		{
			$ret_money = $row['credit'] ;			
			return $ret_money * 100;
		}else return 0;
	}else return 0;
}
// 通过DB的第二次请求到的数据进行查询判断
function check_moneyt($str,$school_id,$money,$card_id)
{
	$db = get_db();
	$stmt = $db->prepare("select * from zf_card_info where user_no = ? and school_id = ? and card_no = ?");
	$stmt -> execute([$str,$school_id,$card_id]);
	$result = $stmt->fetch();
	if($result)
	{
		$stmt1 = $db->prepare("select * from zf_recharge_detail where card_no = :id and (type = 'weixinchongzhi' or type='ZFBJSAPI' or type='WXAPPJSAPI') and is_active = 0 and school_id = :school_id order by id asc limit 1");
		$stmt1->execute([":id"=>$result['card_no'],":school_id"=>$school_id]);
		$row = $stmt1 -> fetch();
		if($row)
		{
			$ret_money = $row['credit'] ;
			//$balance_money = $ret_money + number_format($money/100,2);
			$balance_money = number_format($money/100,2);
			$stmt2 = $db->prepare("update zf_recharge_detail set qctime = :time, is_active = 1, balance = :balance where id = :id and (type = 'weixinchongzhi' or type='ZFBJSAPI' or type='WXAPPJSAPI')");
			$stmt2->execute([":time" => time(),":balance"=>$balance_money,":id"=>$row['id']]);
			
			$stmt3 = $db->prepare("update zf_card_info set balance =  ? where id = ?");
			$stmt3->execute([$balance_money,$result['id']]);
			echo $money;			
			return $ret_money * 100;
		}else return 0;
	}else return 0;
}
