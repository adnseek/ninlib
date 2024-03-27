<?php

class Trace
{
	public $file;
	public $class;
	public $line;
	public $object;
	public $type;
	public $args;

	public function __construct(array $trace)
	{
		foreach ($trace as $key => $val) {
			$this->$key = $val;
		}
	}

}
