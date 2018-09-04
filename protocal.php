
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
function string_handle($str)
{
	// 此处应该用redis把相应的东西存一个月 存储的key的状态为：学校ID:机号:unixtimestamp
	$pos_no = substr($str, 6, 2);
	$head_str = substr($str, 14,2);
    	$str = substr($str, 16, - 4);
	$parts = str_split($str, strlen($str)/intval($head_str));


	$db = get_db();
	foreach ($parts as $row) {
		$info = parse_protocal($row);
		insert_db($info,$db);
		wx_push($info['card_no'],$info['amount'],$info['balance'],$db);

	}
	
	$ret = [];
	$ret[0] = $pos_no;
	$ret[1] = $head_str;
	return join("|",$ret);
}
function parse_protocal($str)
{
	$arr =  [];
	$arr['pos_no'] = hexdec(substr($str, 0,2));
	$arr['card_no'] = parse_cardno(substr($str, 2,6));
	$arr['time'] = substr($str, 8,12);
	$arr['balance'] = number_format(hexdec(substr($str, 20,8))/100,2,'.','');
	$arr['subsidy'] = number_format(hexdec(substr($str, 28,8))/100,2,'.','');
	$arr['amount'] = number_format(hexdec(substr($str,36,8))/100,2,'.','');
	return $arr;
}
function insert_db($arr,$db)
{

	$stmt = $db->prepare("insert into zf_deal_detail (pos_sn,card_no,amount,balance,created,type) values (:pos_no,:card_no,:amount,:balance,:created,:type)");
	$stmt->execute([
		":pos_no" => $arr['pos_no'],
		":card_no" =>   $arr['card_no'] ,
		":amount" => $arr['amount'],
		":balance" => $arr['balance'],
		":created" => strtotime("20".$arr['time']),
		":type" => "shitangshuaka"
	]);
	
	

	$stmt1 = $db->prepare("update zf_card_info set balance = :balance where card_no = :card_no");
	$stmt1->execute([":balance"=>$arr['balance'],"card_no"=>$arr['card_no']]);
	//wx_push($arr['card_no'],$arr['amount'],$arr['balance'],$db);
}

function wx_push($card_no,$money,$balance,$db)
{
	$stmt = $db->prepare("select * from zf_card_info where card_no = ?");
	$stmt -> execute([$card_no]);
	$result = $stmt->fetch();
	if($result)
	{
		//if( $result['balance'] == $balance + $money)
        		//$db->exec("update zf_card_info set balance = $balance where card_no = $card_no");
		$post = [];
		$post['money'] = $money;
		$post['user_no'] = $result['user_no'];
		curl_post($post);
	}

}
function curl_post($post_data)
{
	$url = "http://admin.henanzhengfan.com/site/push";
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	$output = curl_exec($ch);
	curl_close($ch);
}



function get_blacklist()
{
	$db = DatabaseUtils::getDatabase();
	$stmt = $db->prepare("select card_no from zf_card_info where status = 'guashi'");
	$stmt -> execute();
	$result = $stmt->fetchAll();


	$ret_arr = [];
	foreach ($result as $row) {
		$ret_arr[] = $row['card_no'];
	}

	$local = explode("\n",file_get_contents(__DIR__."/black.txt"));
	$remote = $ret_arr;

	$inserect = array_intersect($remote, $local);

	$jiegua = array_diff($local, $inserect);
	$guashi = array_diff($remote, $inserect);

	file_put_contents(__DIR__."/black.txt", join("\n",$remote));
	$ret = [];
	$ret['jiegua'] = join(",",$jiegua);
	$ret['guashi'] = join(",",$guashi);

	return $ret;
}
function parse_cardno($str)
{
	$arr = str_split($str,2);
	$new_arr = array_reverse($arr);
	$new_arr_str = join('',$new_arr);
	return hexdec($new_arr_str);
}
?>
