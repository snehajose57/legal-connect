<?php


//your project path goes here
define("BASE_URL", value: "http://localhost/legalconnect/");
define("BASE_PATH", "c:wamp64/www/legalconnect/");

//set your timezone here
date_default_timezone_set('asia/kolkata');





session_start();
require(BASE_PATH . 'config/db.php');
require(BASE_PATH . 'classes/db.php');
require(BASE_PATH . 'classes/FormAssist.class.php');
require(BASE_PATH . 'classes/FormValidator.class.php');
require(BASE_PATH . 'classes/DataAccess.class.php');



?>