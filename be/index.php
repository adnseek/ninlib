<?php

ini_set('display_errors', true);
error_reporting(E_ALL);
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
	require_once __DIR__ . '/vendor/autoload.php';
} else {
	//require_once '../../../../vendor/autoload.php';
	require_once '../vendor/autoload.php';
}
//require_once 'init.php';

require_once dirname(__FILE__) . '/../AutoLoad.php';


//require_once 'Controller/class.IndexBase.php';	    // force this Index class
require_once __DIR__ . '/class/IndexBE.php';                // force this Index class
$n = new InitNADLIB();
$n->al = AutoLoadBE::getInstance();
//$n->al->debug = true;
$n->init();

require_once __DIR__ . '/class/IndexBE.php';    // force this Index class
$i = Index::makeInstance(Config::getInstance());
$i->initController();
echo $i->render();
AutoLoad::getInstance()->__destruct();
