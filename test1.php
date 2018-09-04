<?php
function loadSchoolConfig($sid, $pos_no) {
                $config = require_once('/data/cron/weixin_config.php');
                if (isset($config[$sid]) && isset($config[$sid][$pos_no])) {
                        return $config[$sid][$pos_no];
                } else {
                        return "餐厅刷卡";
                }

        }

//155
$info = loadSchoolConfig(56650, 155);
echo $info;
