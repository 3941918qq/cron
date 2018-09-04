<?php

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
function register_im($tel, $password) {
	if (empty($tel) || !preg_match("/^1\d{10}$/", $tel)) {
		return false;
	}
	echo "999";
	$posturl = "http://im.henanzhengfan.com:5281/api/register";
	$postData = [
		"user" => $tel,
		"host" => "im.henanzhengfan.com",
		"password" => $password,
	];
	$postData = json_encode($postData);
	$user = "lee@im.henanzhengfan.com";
	$pass = "123456";
	$ch = curl_init(); //初始化curl
	curl_setopt($ch, CURLOPT_URL, $posturl); //抓取指定网页
	curl_setopt($ch, CURLOPT_HEADER, 1); //设置header
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //要求结果为字符串且输出到屏幕上
	curl_setopt($ch, CURLOPT_POST, 1); //post提交方式
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	curl_setopt($ch, CURLOPT_USERPWD, "{$user}:{$pass}");
	$data = curl_exec($ch); //运行curl
	if (strpos('$data', 'successfully registered')) {
		return true;
	} else {
		return false;
	}
}
$db = get_ischooldb();
$stmt = $db->prepare("select tel from wp_ischool_user");
$stmt->execute();
$result = $stmt->fetchAll();
foreach ($result as $row)
	register_im($row['tel'], $row['tel']);



 
