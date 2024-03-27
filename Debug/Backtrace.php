<?php

/**
 * @phpstan-consistent-constructor
 */
class Backtrace
{

	/**
	 * @var Trace[]
	 */
	public $backtrace = [];

	public function __construct()
	{
		$this->backtrace = debug_backtrace();
		foreach ($this->backtrace as &$trace) {
			$trace = new Trace($trace);
		}
	}

	public static function make()
	{
		return new static();
	}

	public function containsClass($className)
	{
		foreach ($this->backtrace as $trace) {
			if ($trace->class === $className || get_class($trace->object) === $className) {
				return true;
			}
		}
		return false;
	}

}
