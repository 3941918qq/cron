<?php
require_once(__DIR__."/weixin_check.php");
$data = "aa4651dd4b565d6b2031e0bb";
preg_match_all("/^aa[0-9A-Za-z]*bb$/", $data, $matches);
//$zhengze ="/aa[0-9A-Za-z]bb/";
if (!empty($matches[0])) {
	$a1 = substr($data, 2, 1);
	$a2 = substr($data, 3, 1);
	$a3 = substr($data, 4, 1);
	$a4 = substr($data, 5, 1);
	$a5 = substr($data, 6, $a1); //学校号
	$a6 = substr($data, $a1 + 6, $a2); //卡号编号
	$a7 = substr($data, $a1 + 6 + $a2, $a3); //UID卡号
	//     $a8 = substr($data,$a1+6+$a2+$a3,$a4); //钱
	$a8 = substr($data, $a1 + 6 + $a2 + $a3, -2); //钱
	$card_no = hexdec($a6);
	$sid = hexdec($a5);
	$money = hexdec($a8);
	$card_id = hexdec($a7);
	$result = check_money($card_no, $sid, $money, $card_id);
	echo "-----" . $card_no . "------" . $sid . "\n";
	echo date("Y-m-d H:i:s") . "------" . $result . "\n";
	$a8 = dechex($result);
	$a4 = strlen($a8);
	$strs = "cc" . $a1 . $a2 . $a3 . $a4 . $a5 . $a6 . $a7 . $a8 . "dd";
	echo $strs;
}
preg_match_all("/^ee[0-9A-Za-z]*ff$/", $data, $matche);
if (!empty($matche[0])) {
	$a1 = substr($data, 2, 1);
	$a2 = substr($data, 3, 1);
	$a3 = substr($data, 4, 1);
	$a4 = substr($data, 5, 1);
	$a5 = substr($data, 6, $a1); //学校号
	$a6 = substr($data, $a1 + 6, $a2); //卡号
	$a7 = substr($data, $a1 + 6 + $a2, $a3); //UID
	// $a8 = substr($data,$a1+6+$a2+$a3,$a4);       //钱
	$a8 = substr($data, $a1 + 6 + $a2 + $a3, -2); //钱
	$card_no = hexdec($a6);
	$sid = hexdec($a5);
	$money = hexdec($a8);
	$card_id = hexdec($a7);
	echo "--22-------" . $card_no . "\n" . $money . "------";
	$result = check_moneyt($card_no, $sid, $money, $card_id);
	echo date("Y-m-d H:i:s") . "---" . $data . "------" . $sid . "-------" . $result . "\n";
	//$a8=dechex($result);
	//$a4=strlen($a8);
	// $strs = "cc".$a1.$a2.$a3.$a4.$a5.$a6.$a7.$a8."dd";
	// $serv->send($fd,$strs);
}

