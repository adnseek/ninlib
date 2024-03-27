<?php

require_once __DIR__.'/../bootstrap.php';
require_once __DIR__.'/TestConfig.php';
if (is_file(__DIR__ . '/../vendor/autoload.php')) {
	require_once __DIR__ . '/../vendor/autoload.php';
}

// this is a problem
// to fix - implement your test config
// and inject it manually
//if (!class_exists('Config')) {
//	class_alias(TestConfig::class, 'Config');
//	Config::getInstance()->postInit();
//}

require_once __DIR__.'/AppController4Test.php';

// belongs to the app code
//function __($a, ...$replacements) { return $a; }

echo basename(__DIR__).'/'.basename(__FILE__), ' done', BR;
