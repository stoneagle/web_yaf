<?php
ini_set("display_errors", "On");
define('APP_PATH', dirname(__FILE__));
$application = new Yaf_Application( APP_PATH . "/conf/application.ini");
$application->bootstrap()->run();
