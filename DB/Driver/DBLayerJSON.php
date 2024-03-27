<?php

/**
 * @method  runDeleteQuery($table, array $where)
 */
class DBLayerJSON extends DBLayerBase implements DBInterface
{

	public $folderName;

	public $tables = [];

	public $currentQuery;

	public function __construct($folderName)
	{
		$this->folderName = $folderName;
	}

	/**
	 * @param string $name
	 * @return DBLayerJSONTable
	 */
	public function getTable($name)
	{
		$name = trim($name);
		if (!isset($this->tables[$name])) {
			$this->tables[$name] = new DBLayerJSONTable(cap($this->folderName) . $name . '.json');
		}
		return $this->tables[$name];
	}

	public function fetchAll($res_or_query, $index_by_key = null)
	{
		if ($res_or_query instanceof DBLayerJSONTable) {
			return $res_or_query->fetchAll($res_or_query, $index_by_key);
		} else {
			debug($res_or_query, $this->currentQuery);
			$table = $this->extractTable($res_or_query ?: $this->currentQuery);
			if ($table) {
				$t = $this->getTable($table);
				return $t->fetchAll($res_or_query, $index_by_key);
			} else {
				debug($res_or_query);
				throw new InvalidArgumentException('Unable to find table name after FROM in SQL');
			}
		}
	}

	public function extractTable($res_or_query)
	{
		$del = " \n\t";
		$tokens = [];
		for ($tok = strtok($res_or_query, $del); $tok !== false; $tok = strtok($del)) {
			$tokens[] = $tok;
		}
		$iFROM = array_search('FROM', $tokens);
		$table = ifsetor($tokens[$iFROM + 1]);
		return $table;
	}

	public function fetchAssoc($res)
	{
		if ($res instanceof DBLayerJSONTable) {
			return $res->fetchAssoc($res);
		} else {
			throw new InvalidArgumentException('fetchAssoc needs to have reference to the DBLayerJSONTable');
		}
	}

	public function numRows($res = null)
	{
		//debug(gettype2($res));
		if (!($res instanceof DBLayerJSONTable)) {
			$table = $this->extractTable($res);
			if ($table) {
				$t = $this->getTable($table);
			} else {
				throw new InvalidArgumentException(__METHOD__);
			}
		} else {
			/** @var DBLayerJSONTable $t */
			$t = $res;
		}
		return $t->numRows($res);
	}

	public function runInsertQuery($table, array $data)
	{
		$t = $this->getTable($table);
		return $t->runInsertQuery($table, $data);
	}

	public function runUpdateQuery($table, array $data, array $where)
	{
		$t = $this->getTable($table);
		return $t->runUpdateQuery($table, $data, $where);
	}

	public function getSelectQuery($table, array $where = [], $order = '', $addSelect = null)
	{
		$query = parent::getSelectQuery($table, $where, $order, $addSelect);
		$this->currentQuery = $query;

		$t = $this->getTable($table);
		$t->where = $where;
		return $t;
	}

	public function runSelectQuery($table, array $where = [], $order = '', $addSelect = '')
	{
		$t = $this->getTable($table);
		$res = $t->runSelectQuery($table, $where, $order, $addSelect);
		return $res;
	}

	public function __call($method, array $params)
	{
//		echo $method, BR;
		return parent::__call($method, $params);
	}

	public function perform($query, array $params = [])
	{
		$this->currentQuery = $query;
		parent::perform($query, $params);
		if (!($query instanceof DBLayerJSONTable)) {
			$table = $this->extractTable($query);
			$t = $this->getTable($table);
		} else {
			$t = $query;
		}
		return $t;
	}

	public function getInfo()
	{
		return ['class' => get_class($this)];
	}

	public function getVersion()
	{
		// TODO: Implement getVersion() method.
	}
}
