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

function get_blacklist()
{
	$db = get_db();
	$stmt = $db->prepare("select card_no from zf_guashi");
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
	
	$str_jiegua = join(",",$jiegua);
	$str_guashi = join(",",$guashi);

	return $str_guashi . "|" . $str_jiegua;
}
