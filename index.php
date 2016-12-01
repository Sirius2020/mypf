<?php

define('APPLICATION_PATH', dirname(__FILE__));

$application = new Yaf_Application( APPLICATION_PATH . "/conf/application.ini");

define('ROOT', APPLICATION_PATH.'/application');

$application->bootstrap()->run();


?>
