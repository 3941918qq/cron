<?php
class sendWeiXin {

	static $COM_KF_URL = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=";
	static $COM_MB_RUL = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=";
	static $COM_PIC_URL = "/upload/syspic/msg.jpg";
	//正梵id
	static $temp_id_0 = "sEKt1Tv3tBwZa6DmBXIuXV7cY4OEpZDv9mWiqNQDSfM";
	static $temp_id_1 = "sEKt1Tv3tBwZa6DmBXIuXV7cY4OEpZDv9mWiqNQDSfM";
	//三高瑞贝卡的模板id
	static $temp_id_2 = "Gkdbs0OgYRDcG9D-zDyWiIsCSI3SfoLynF0_SpxcA_U";
	static $temp_id_3 = "_DMvajJ6SyBCOqmj_Nd9GmyRLGn1eUDqSFiLB2rxZqQ";
	static $temp_id_4 = "ao25ZiEXR2RF-ba3T4pwfVOTvOcwrTTu_owu1dqBotc";
	static $temp_id_5 = "cUe1pyf3nNra0-jSSVsz6mxpKpZzl-m-pkBO0miBhcY";
	static $temp_id_6 = "gkFvx4gAJOiYOoLeZzHRSPg_LQuAEtqd1KYcttVvZow";
	static function getUrl($type,$sid) {
		if($sid=='56650' || $sid == '56651'){
			$access_token = self::getRbkAccessToken();
		}else{
			$access_token = self::getAccessToken();
		}		
		if ($type == 'kf') {
			$url = self::$COM_KF_URL . $access_token;
		} else {
			$url = self::$COM_MB_RUL . $access_token;
		}
		return $url;
	}
	static function getRbkAccessToken(){	
                $appId = "wxc5c7e311f8d5d759";
                $appSecret = "e6ccb6b6817cfe5e9c58bc360b0a05b7";
                $token_url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appId . "&secret=" . $appSecret;
                $redis = new redis();
                $result = $redis->connect('127.0.0.1', 6379);
                if ($result) {
                        $acc_token = $redis->get("my_access_token_one");
                        if (!$acc_token) {
                                $json = file_get_contents($token_url);
                                $result = json_decode($json);
                                $acc_token = $result->access_token;
                                $redis->set("my_access_token_one", $acc_token, 7100);
                        }

                } else {
                        $json = file_get_contents($token_url);
                        $result = json_decode($json);
                        $acc_token = $result->access_token;
                }

                return $acc_token;

	}
	static function getAccessToken() {
		$appId = "wx8c6755d40004036d";
		$appSecret = "22f68f4da5b36641ed492c596406b75f";
		$token_url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appId . "&secret=" . $appSecret;

		$redis = new redis();
		$result = $redis->connect('127.0.0.1', 6379);
		if ($result) {
			$acc_token = $redis->get("my_access_token");
			if (!$acc_token) {
				$json = file_get_contents($token_url);
				$result = json_decode($json);
				$acc_token = $result->access_token;
				$redis->set("my_access_token", $acc_token, 7100);
			}

		} else {
			$json = file_get_contents($token_url);
			$result = json_decode($json);
			$acc_token = $result->access_token;
		}

		return $acc_token;
	}
	static function createNewsMsg($openid, $title, $content, $url, $picurl) {
		if (empty($picurl) || $picurl == "") {
			$picurl = C("URL_PATH") . self::$COM_PIC_URL;
		}
		return '{
            "touser":"' . $openid . '",
            "msgtype":"news",
            "news":
                {
                  "articles":[
                    {
                      "title":"' . $title . '",
                      "description":"' . $content . '",
                      "url":"' . $url . '",
                      "picurl":"' . $picurl . '"
                    }
                  ]
                }
            }';
	}
	static function getTempid($index) {
		// if ($index % 2 == 0) {
		// 	return self::$temp_id_0;
		// } else {
		// 	return self::$temp_id_1;
		// }
		if($index==0){
			return self::$temp_id_0;
		}else if($index==1){
			return self::$temp_id_1;
		}else if($index==2){
			return self::$temp_id_2;
		}else if($index==3){
			return self::$temp_id_3;
		}else if($index==4){
			return self::$temp_id_4;
		}else if($index==5){
			return self::$temp_id_5;
		}else if($index==6){
			return self::$temp_id_6;
		}
		
	}
	static function getDB() {
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

	static function singlePostMsg($url, $data) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
		$result = curl_exec($curl);
		if (curl_errno($curl)) {
			return array("errcode" => -1, "errmsg" => '发送错误号' . curl_errno($curl) . '错误信息' . curl_error($curl));
		}
		curl_close($curl);
		return $result;
	}
	static public function loadSchoolConfig($sid, $pos_no) {
		$config = require_once('/data/cron/weixin_config.php');
		var_dump($config);
		if (isset($config[$sid]) && isset($config[$sid][$pos_no])) {
			return $config[$sid][$pos_no];
		} else {
			return "餐厅刷卡";
		}

	}	
	static public function sendCard($post) {
		if(!isset($post['sid']) || !isset($post['user_no'])) return -1;
		/*
		if (strlen($post['user_no']) == 7) {
			$user_no = "T".$post['sid'].$post['user_no'];
		}elseif (strlen($post['user_no']) ==8) {
			$user_no = "53".$post['user_no'];
		}*/
		
		$user_no = $post['user_no'];

		$db = self::getDB();
		$sql = "SELECT wp_ischool_pastudent.openid from wp_ischool_student,wp_ischool_pastudent WHERE wp_ischool_student.stuno2 = :user_no and wp_ischool_student.id = wp_ischool_pastudent.stu_id and wp_ischool_pastudent.openid is not null and wp_ischool_student.sid=:sid order by wp_ischool_pastudent.id desc";
		$stmt = $db->prepare($sql);
		$stmt->execute([":user_no" => $user_no,":sid"=>$post['sid']]);
		$ret = $stmt->fetchAll();
		if (empty($ret)) {
			return -2;
		}

		$title = "您的孩子" . $post['name'] . "有一条消费信息";
		$sid = $post['sid'];
		$url = self::getUrl('mb',$sid);
		// $random_num = random_int(1, 2);
		//$sid = $post['sid'];
		if($sid!='56650'){
			$random_num =rand(0,1);
		}else{
			$random_num =rand(2,6);
		}
		$tempId = self::getTempid($random_num);
		echo $sid . $post['pos_no'];
		$dealtype = self::loadSchoolConfig($sid, $post['pos_no']);
		echo $dealtype;
		$final_ret = [];
		foreach($ret as $row){ 
			//$dealtype = self::loadSchoolConfig($sid, $post['pos_no']);
			$data = self::createTempMsg($row['openid'], $tempId, $title, $post['money'],$post['balance'],$post['time'],$dealtype);
			$result = json_decode(self::singlePostMsg($url, $data),true);
			$result['user_no'] = $user_no;
			$result['timestamp'] = date("Y-m-d H:i:s");
			$final_ret[] = $result;
		}
		return $final_ret;

	}
	static function createTempMsg($openid, $tempid, $title, $amount, $balance,$timestamp,$dealtype,$url = "") {
		return '{
                       "touser":"' . $openid . '",
                       "template_id":"' . $tempid . '",
                       "url":"' . $url . '",
                       "topcolor":"#FF6666",
                       "data":{
                           "first":{
                               "value":"' . $title . '\n",
                               "color":"#000000"
                           },
                            "keyword1":{
                               "value":"' . date("Y年m月d日H时i分s秒",$timestamp) . '\n",
                               "color":"#000000"
                           },
                           "keyword2":{
                               "value":"' . $amount . '元\n",
                               "color":"#000000"
                           },
                           "keyword3":{
                               "value":"'.$dealtype.'\n",
                               "color":"#000000"
                           },
                           "keyword4":{
                               "value":"' . $balance . '元\n",
                               "color":"#000000"
                           },
                          "remark":{
                               "value":"正梵智慧校园感谢您的支持。",
                               "color":"#000000"
                           }
                       }
              }';

	}
}
