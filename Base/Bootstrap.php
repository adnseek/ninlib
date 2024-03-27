<?php

namespace spidgorny\nadlib;

use Request;

class Bootstrap
{

	public function boot()
	{
		echo 'cwd: ', basename(getcwd()), "\n";

		require_once __DIR__ . '/../init.php';
		@define('BR', Request::isWindows() ? "\r\n" : "\n");

		// first in order to load phpunit classes
		$globalAutoload = getenv('USERPROFILE') .
			DIRECTORY_SEPARATOR . 'AppData' .
			DIRECTORY_SEPARATOR . 'Roaming' .
			DIRECTORY_SEPARATOR . 'Composer' .
			DIRECTORY_SEPARATOR . 'vendor' .
			DIRECTORY_SEPARATOR . 'autoload.php';
		echo $globalAutoload, BR;
		if (is_file($globalAutoload)) {
			/** @noinspection PhpIncludeInspection */
			include_once $globalAutoload;
		}

		require_once __DIR__ . '/../Base/ConfigBase.php';
		//require_once 'TestConfig.php';
		//class_alias('TestConfig', 'Config');

		$_COOKIE['debug'] = 1;

		require_once __DIR__ . '/../Base/InitNADLIB.php';
//		$n = new \InitNADLIB();
		//$n->init();

//		$al = AutoLoad::getInstance();
//		$al->addFolder(__DIR__);

//		$this->loadVendorAutoload();

		//debug(spl_autoload_functions());

		echo 'bootstrap.php done', BR;
	}

	public function loadVendorAutoload()
	{
		$path = trimExplode('/', str_replace('\\', '/', getcwd()));
		//debug($path);
		foreach (range(sizeof($path), 0, -1) as $i) {
			$dir = implode(DIRECTORY_SEPARATOR, array_slice($path, 0, $i));
			$autoloadPHP = $dir . '/vendor/autoload.php';
			echo $autoloadPHP, BR;
			if (file_exists($autoloadPHP)) {
				/** @noinspection PhpIncludeInspection */
				require_once $autoloadPHP;
				echo $autoloadPHP, BR;
				break;
			}
		}
	}

}
