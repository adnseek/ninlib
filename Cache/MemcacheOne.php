<?php

class MemcacheOne
{

	/**
	 * @var string
	 */
	protected $key;

	/**
	 * @var int
	 */
	protected $expires;

	/**
	 * @var MemcacheFile
	 */
	protected $mf;

	/**
	 * @var mixed
	 */
	protected $value;

	public function __construct($key, $expires = 3600)
	{
		$this->key = $key;
		$this->expires = $expires;
		$this->mf = new MemcacheFile();
		$this->value = $this->mf->get($this->key, $this->expires);
	}

	public function is_Set()
	{
		return !!$this->value;
	}

	public function getValue()
	{
		return $this->value;
	}

	public function set($newValue)
	{
		$this->mf->set($this->key, $newValue);
		$this->value = $newValue;
	}

	public function getAge()
	{
		return $this->mf->getAge($this->key);
	}

	public function map()
	{
		return $this->mf->map($this->key);
	}

}
