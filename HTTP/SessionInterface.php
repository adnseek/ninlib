<?php

namespace nadlib\HTTP;

interface SessionInterface
{

	public function get($key, $default = null);

	public function save($key, $val);

	public function has($key);

	public function getAll();

	public function delete($key);

}
