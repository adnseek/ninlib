<?php

class MemCacheRedis implements MemcacheInterface
{

	protected $predis;

	public function __construct(Predis\Client $predis)
	{
		$this->predis = $predis;
	}

	public function get($key)
	{
		$value = $this->predis->get($key);
		try {
			return json_decode($value, false, 512, JSON_THROW_ON_ERROR);
		} catch(Exception $e) {
			return $value;
		}
	}

	public function set($key, $val)
	{
		return $this->predis->set($key, json_encode($val, JSON_THROW_ON_ERROR));
	}

	public function isValid($key = null, $expire = 0)
	{
		return $this->predis->ttl($key);
	}
}
