<?php

use spidgorny\nadlib\HTTP\URL;

class AppRootDetector
{

	/**
	 * @var Path
	 */
	protected $appRoot;

	var $debug = false;

	public $nadlibRoot = 'vendor/spidgorny/nadlib/';

	/**
	 * Original idea was to remove vendor/s/nadlib/be from the CWD
	 * but since $this->nadlibRoot is relative "../" it's impossible.
	 * Now we go back one folder until we find "class/class.Config.php" which MUST exist
	 *
	 * Since it's not 100% that it exists we just take the REQUEST_URL
	 */
	function __construct()
	{
		$this->log(Request::isPHPUnit(), Request::isCLI());
		if (Request::isPHPUnit()) {
			$appRoot = getcwd();
		} elseif (Request::isCLI()) {
//			echo TAB, __METHOD__, ': ', getcwd(), BR;
			$appRoot = getcwd();
			$appRoot = new Path(cap($appRoot));
		} else {
			$appRoot = dirname(URL::getScriptWithPath());
			$appRoot = str_replace('/kunden', '', $appRoot); // 1und1.de
		}
		$appRoot = realpath($appRoot);
		$this->log('$this->appRoot', $appRoot, $this->nadlibRoot);
		//$this->appRoot = str_replace('/'.$this->nadlibRoot.'be', '', $this->appRoot);
		while ($appRoot && ($appRoot != '/' && $appRoot != '\\')
			&& !($appRoot[1] === ':' && strlen($appRoot) === 3)    // u:\
		) {
			$config1 = $appRoot . DIRECTORY_SEPARATOR . 'index.php';
			$exists1 = file_exists($config1);
			$this->log(__METHOD__, ' ', $config1, ': ', $exists1);
			$this->log($appRoot, strlen($appRoot), $exists1);
			if ($exists1) {
				break;
			}
			$appRoot = dirname($appRoot);
		}

		if (!$appRoot || $appRoot == '/') {  // nothing is found by previous method
			$this->log(__METHOD__, ' Alternative way of app root detection');
			$appRoot = new Path(realpath(dirname(URL::getScriptWithPath())));
			$this->log($appRoot, URL::getScriptWithPath());
			$appRoot->upIf('nadlib');
			$appRoot->upIf('spidgorny');
			$appRoot->upIf('vendor');
			$hasIndex = $appRoot->hasFile('index.php');
			$this->log($appRoot . '', $hasIndex);
			if (!$hasIndex) {
				$appRoot->up();
			}
		}

		$this->log($appRoot);
		// always add trailing slash!
		$appRoot = cap($appRoot, '/');
		$appRoot = new Path($appRoot);
		$this->appRoot = $appRoot;

		if ($this->debug && !Request::isCLI()) {
//			exit;
		}
	}

	public function get()
	{
		return $this->appRoot;
	}

	public function log($a)
	{
		if ($this->debug) {
			echo __METHOD__, ' ', implode(' ', func_get_args()), BR;
		}
	}

}
