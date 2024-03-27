<?php

trait DirectDataAccess
{

	public $data = [];

	public function __get(string $key)
	{
		return $this->data[$key] ?? null;
	}

	public function __set(string $key, $val)
	{
		$this->data[$key] = $val;
	}

	public function __isset(string $key)
	{
		return (bool)$this->data[$key];
	}

}
