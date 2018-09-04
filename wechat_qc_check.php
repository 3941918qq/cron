<?php
function crc($info) {
      $data = str_split($info, 2);
      $crc = 0x00;
      for ($i = 0; $i < count($data); $i++) {
         $temp = hexdec($data[$i]);
          $crc ^= ($temp & 0xff);
      }
      $big = ($crc >> 8) & 0xff;
      $little = $crc & 0xff;
      return sprintf("%02x", $big) . sprintf("%02x", $little);
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




// 通过DB的不再说明
function check_money($phyid,$school_id)
{
	$db = get_db();

	$stmt = $db->prepare("select * from zf_card_info where phyid = ? and school_id = ? and status='zhengchang'");
	$stmt -> execute([$phyid,$school_id]);
	$result = $stmt->fetch();
	if($result)
	{
		$stmt1 = $db->prepare("select * from zf_recharge_detail where card_no = :id and type = 'weixinchongzhi' and is_active = 0 and school_id = :school_id order by id asc");
		$stmt1->execute([":id"=>$result['card_no'],":school_id"=>$school_id]);
		$row = $stmt1 -> fetchAll();
		if($row)
		{			
			return $row;
		}else return 0;
	}else return 0;
}
// 通过DB的第二次请求到的数据进行查询判断
function check_moneyt($phyid,$school_id,$money)
{
	$db = get_db();
	$stmt = $db->prepare("select * from zf_card_info where phyid = ? and school_id = ?");
	$stmt -> execute([$phyid,$school_id]);
	$result = $stmt->fetch();
	if($result)
	{
		$stmt1 = $db->prepare("select * from zf_recharge_detail where card_no = :id and type = 'weixinchongzhi' and is_active = 0 and school_id = :school_id order by id asc");
		$stmt1->execute([":id"=>$result['card_no'],":school_id"=>$school_id]);
		$row = $stmt1 -> fetchAll();
		if($row)
		{
			foreach($row as $k=>$v){
				$ret_money+=$v['credit'];
				$balance_money = number_format($money/100,2,'.','');
				// echo $balance_money;
				$stmt2 = $db->prepare("update zf_recharge_detail set qctime = :time, is_active = 1, balance = :balance where id = :id and type = 'weixinchongzhi'");
			    $stmt2->execute([":time" => time(),":balance"=>$balance_money,":id"=>$v['id']]);		
			    $stmt3 = $db->prepare("update zf_card_info set balance =  ? where id = ?");
			    $stmt3->execute([$balance_money,$result['id']]);
			}									
			 echo $money;			
			return $ret_money * 100;
		}else return 0;
	}else return 0;
}
