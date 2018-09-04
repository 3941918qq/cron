<?php
        $redis = new redis();
        $result = $redis->connect('127.0.0.1', 6379);
//        $redis_exist = $redis->sIsMember('card_temp_pool', $redis_set_key);
	$redis->delete("card_temp_pool");

