<?php

use spidgorny\nadlib\HTTP\URL;

class MiniRouter
{

	protected $basePath;

	public function __construct($basePath = '')
	{
		$this->basePath = $basePath;
	}

	public function handleRequest()
	{
		if (!ifsetor($_SERVER['REQUEST_URI'])) {
			return true;
		}
		//debug($_SERVER);
//		llog($_SERVER['REQUEST_URI']);
		$requestURL = new URL($_SERVER['REQUEST_URI']);
		//debug($requestURL, $requestURL->getPath().'', is_file($requestURL->getPath()));
		$staticPath = $requestURL->getPath();
		llog('BasePath: ' . $this->basePath . '');
		llog('StaticPath: ' . $staticPath . '');
		if ($this->basePath) {
			$last = basename($this->basePath);
			llog('Basename: ' . basename($this->basePath));
			$staticPath->remove($last);
		}
		llog('StaticPath: ' . $staticPath . '');
		if ($staticPath == '/') {
			return null;  // default index controller
		} elseif ($staticPath) {
			// vendor/spidgorny/nadlib/HTTP
			$fullPath = realpath(__DIR__ . '/../../../../' . $staticPath);
//			llog($fullPath);
			if (is_file($fullPath)) {
//				llog(['fullPath' => $fullPath]);
				return false;
			} else {
				// Windows
				$staticPath = str_replace('\\', '/', $staticPath);
				$parts = trimExplode('/', $staticPath);
				$first = first($parts);
				if ($first && !class_exists($first)) {
					http_response_code(404);
					header('X-Path: ' . $fullPath);
					//echo 'Class '.$first.' not found';
					return;
				} else {
					return $first;  // the class from the URL
				}
			}
		}
		return $staticPath;  // true means PHP
	}

}
