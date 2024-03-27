<?php

class MemcacheDB implements MemcacheInterface
{

	/**
	 * @var DBLayerBase
	 */
	public $db;

	public $table = 'cache';

	public function __construct()
	{
		$this->db = Config::getInstance()->getDB();
	}

	public function get($key)
	{
		$row = $this->getRow($key);
		return $row['value'];
	}

	public function getRow($key)
	{
		$row = $this->db->fetchOneSelectQuery($this->table, [
			'key' => $key,
		]);
		return $row;
	}

	public function set($key, $value)
	{
		$this->db->runUpdateInsert($this->table, [
			'value' => $value,
			'mtime' => new SQLNow(),
		], [
			'key' => $key,
		]);
	}

	public function isValid($key = null, $expire = 0)
	{
		$row = $this->getRow($key);
		return (strtotime($row['mtime']) - time()) < $expire;
	}

	public function un_set($key)
	{
		$this->db->runDeleteQuery($this->table, [
			'key' => $key,
		]);
	}

}
