<?php

class CResolver implements ResolverInterface
{

	/** @var string */
	public $slug;

	public function __construct($slug)
	{
		$this->slug = $slug;
	}

	public function getController()
	{
		$controller = $this->slug;
		// to simplify URL it first searches for the corresponding controller
		$ptr = &Config::getInstance()->config['autoload']['notFoundException'];
		$tmp = $ptr;
		$ptr = false;
		if ($controller && class_exists($controller . 'Controller')) {
			$controller = $controller . 'Controller';
		}
		$ptr = $tmp;

		$Scontroller = new Path($controller);
		if ($Scontroller->length() > 1) {    // in case it's with sub-folder
			$dir = dirname($Scontroller);
			$parts = trimExplode('/', $controller);
			//debug($dir, $parts, file_exists($dir));
			if (file_exists($dir)) {
				$controller = end($parts);
			} else {
				$controller = first($parts);
			}
		} else {
			//debug($controller);
			//die(__METHOD__);
			$controller = $controller . '';    // OK
		}
		return $controller;
	}

}
