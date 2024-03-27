<?php

class MockPreferences
{

	protected $user;

	protected $data = [];

	public function __construct(UserModelInterface $user)
	{
		$this->user = $user;
	}

	public function set($key, $val)
	{
		$this->data[$key] = $val;
	}

	public function get($key)
	{
		return $this->data[$key];
	}

	public function getData()
	{
		return $this->data;
	}

}