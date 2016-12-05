<?php

define('APPLICATION_PATH', dirname(__FILE__));

$application = new Yaf_Application( APPLICATION_PATH . "/conf/application.ini");

define('ROOT', APPLICATION_PATH.'/application');

date_default_timezone_set('Asia/Shanghai');

$application->bootstrap()->run();


?>
