<?php
//连接数据库取数据
function get_new_data(){	
    $db=get_db();
    $time=time()-900;
    $sql="SELECT * FROM change_data WHERE ctime > $time";
    $stmt=$db->query($sql);
    $count=$stmt->rowCount();
    $all=$stmt->fetchAll();
    //将数据组织格式  
    foreach($all as $key=>$data_info){
    	//取出相应的数据
        if(strlen($data_info['surfaceid'])>20){
            $sql1="SELECT * FROM ".$data_info['datasurface']." WHERE openid = '".$data_info['surfaceid']."'";
        }else{
            $sql1="SELECT * FROM ".$data_info['datasurface']." WHERE id = '".$data_info['surfaceid']."'";
        }   	
	    $stmt=$db->query($sql1);
        
	    $row=$stmt->fetch();	    
        $data_info['data']=json_encode($row);
    	// $url='http://mobile.henanzhengfan.com/uploadimage/ceshi'; 
        $baseinfo="{'TYPE':'POST','IP':'123.206.45.159'}"; 
        $token=base64_encode(md5($baseinfo));
        $url="http://218.28.73.131?token=".$token;
    	$result=PostCurl($url,$data_info);
    	var_dump($result);
    }


}
function get_db(){
    $pdo = new PDO(
            'mysql:host=localhost;dbname=ischool',
            'root',
            'hnzf123456',
            array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'")
        );
    $pdo->setAttribute( PDO::ATTR_ERRMODE , PDO::ERRMODE_EXCEPTION );
    $pdo->setAttribute( PDO::ATTR_DEFAULT_FETCH_MODE , PDO::FETCH_ASSOC);
    return $pdo;
}
//发送post请求三高服务器修改相关记录 
function PostCurl($url,$data){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		//设置header
		curl_setopt($curl, CURLOPT_HEADER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
		curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
		$result = curl_exec($curl);
		if (curl_errno($curl)) {
			return array("errcode"=>-1,"errmsg"=>'发送错误号'.curl_errno($curl).'错误信息'.curl_error($curl));
		}

		curl_close($curl);
		return $result;
}
get_new_data();
