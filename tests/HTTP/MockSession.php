<?php

use nadlib\HTTP\SessionInterface;

/**
 * Class MockSession
 * When running in PHPUnit we get
 * session_start(): Cannot send session cookie - headers already sent
 * Therefore we hack session with files.
 */
class MockSession implements SessionInterface
{

	public $file;

	public $data = [];

	public function __construct($file)
	{
		$this->file = $file;
		if (is_file($file)) {
			$this->data = unserialize(
				file_get_contents($this->file)
			);
		}
	}

	public function __destruct()
	{
		file_put_contents($this->file, serialize($this->data));
	}

	public function save($key, $val)
	{
		$this->data[$key] = $val;
	}

	public function get($key, $default = null)
	{
		return ifsetor($this->data[$key]);
	}

	public function has($key)
	{
		return !!ifsetor($this->data[$key]);
	}

	public function getAll()
	{
		return $this->data;
	}

	public function delete($key)
	{
		unset($this->data[$key]);
	}

}
