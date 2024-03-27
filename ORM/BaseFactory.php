<?php

class BaseFactory
{

	static public $instances = [];

//	function __invoke() {
//		throw new InvalidArgumentException('Please implement '.get_class($this).'->__invoke()');
//	}

	public static function getInstance()
	{
		$class = get_called_class();
		if (!ifsetor(self::$instances[$class])) {
			self::$instances[$class] = new $class();
		}
		return self::$instances[$class];
	}

}
