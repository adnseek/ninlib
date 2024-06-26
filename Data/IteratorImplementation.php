<?php

class IteratorImplementation
{

	public $data = [];

	private $position = 0;

	public function rewind()
	{
		$this->position = 0;
	}

	public function current()
	{
		return $this->data[$this->position];
	}

	public function key()
	{
		return $this->position;
	}

	public function next()
	{
		++$this->position;
	}

	public function valid()
	{
		return isset($this->data[$this->position]);
	}

}
