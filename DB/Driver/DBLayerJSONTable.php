<?php

/**
 * @method  getSelectQuery($table, array $where = [], $order = '', $addSelect = '')
 * @method  runDeleteQuery($table, array $where)
 */
class DBLayerJSONTable extends DBLayerBase implements DBInterface
{

	public $filename;

	public $data = [];

	public $where = [];

	public function __construct($filename)
	{
		$this->filename = $filename;
		if (is_file($this->filename)) {
			$json = file_get_contents($this->filename);
			$this->data = json_decode($json, true);
		} else {
			file_put_contents($this->filename, '[]');
		}
	}

	public function __destruct()
	{
		file_put_contents($this->filename,
			json_encode($this->data, JSON_PRETTY_PRINT));
	}

	public function __toString()
	{
		$query = 'SELECT * FROM ' . basename($this->filename) . ' WHERE ' .
			json_encode($this->where);
		//echo $query, BR;
		return $query;
	}

	public function fetchAll($res_or_query, $index_by_key = null)
	{
		//return $this->data;
		$data = [];
		reset($this->data);
		while (!is_null(key($this->data))) {
			$newRow = $this->fetchAssoc($this->data);
			//debug($newRow);
			if (!empty($newRow)) {
				$data[] = $newRow;
			}
			next($this->data);
		}
		return $data;
	}

	public function fetchAssoc($res)
	{
		$row = null;
		if ($this->where) {
			while (!is_null(key($this->data))) {
				$row = current($this->data);
				if (self::matchWhere($row, $this->where)) {
					return $row;
				}
				// only if false (next() is called in fetchAll())
				next($this->data);
			}
			$row = null;    // last row was not returned
		} else {
			$row = current($this->data);
		}
//		debug($row, $this->where);
		return $row;
	}

	public static function matchWhere(array $row, array $where)
	{
		foreach ($where as $key => $val) {
			$matchOne = (string)$row[$key] == (string)$val;
			//debug($row[$key], $val, $matchOne);
			if (!$matchOne) {
				return false;
			}
		}
		return true;
	}

	public function numRows($res = null)
	{
		return sizeof($this->data);
	}

	public function runInsertQuery($table, array $data)
	{
		$this->data[] = $data;
	}

	public function runUpdateQuery($table, array $data, array $where)
	{
		foreach ($this->data as &$row) {
			if ($this->matchWhere($row, $where)) {
				$row = array_merge($row, $data);
			}
		}
	}

	public function runSelectQuery($table, array $where = [], $order = '', $addSelect = null)
	{
		$this->where = $where;
		return $this;
	}

	public function perform($query = null, array $params = [])
	{
		return $this;
	}

	public function getInfo()
	{
		return ['class' => get_class($this)];
	}

	public function getVersion()
	{
		// TODO: Implement getVersion() method.
	}

	public function __call($method, array $params)
	{
		// TODO: Implement @method  getSelectQuery($table, array $where = [], $order = '', $addSelect = '')
		// TODO: Implement @method  runDeleteQuery($table, array $where)
	}
}
