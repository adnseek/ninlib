<?php

interface MemcacheInterface
{

	public function get($key);

	public function set($key, $val);

	public function isValid($key = null, $expire = 0);

}
