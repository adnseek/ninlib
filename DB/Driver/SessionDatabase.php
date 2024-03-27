<?php

namespace nadlib;

use ArrayPlus;
use DBInterface;
use nadlib\HTTP\Session;
use Nette\NotImplementedException;
use SQLSelectQuery;
use SQLWhere;

/**
 * @method  fetchSelectQuery($table, $where = [], $order = '', $addFields = '', $idField = null)
 * @method  describeView($viewName)
 * @method  getFirstValue($query)
 * @method  performWithParams($query, $params)
 * @method  getConnection()
 * @method  getViews()
 * @method  runSelectQuery($table, array $where = [], $order = '', $addSelect = '')
 * @method  getInsertQuery($table, array $data)
 * @method  getDeleteQuery($table, array $where = [], $what = '')
 * @method  getUpdateQuery($table, array $set, array $where)
 * @method  runDeleteQuery($table, array $where)
 * @phpstan-consistent-constructor
 */
class SessionDatabase implements DBInterface
{

	/**
	 * @var static
	 */
	protected static $instance;
	/**
	 * @var array
	 */
	public $data = [];
	/**
	 * @var \Session
	 */
	protected $session;

	/**
	 * @phpstan-consistent-constructor
	 */
	public function __construct()
	{
		$this->session = new Session(__CLASS__);
		$data = $this->session->getAll();
		foreach ($data as $table => $rows) {
			$this->data[$table] = $rows;
		}
	}

	public static function initialize()
	{
		if (!self::$instance) {
			self::$instance = new static();
		}
		return self::$instance;
	}

	public function __destruct()
	{
		foreach ($this->data as $table => $rows) {
			$this->session->save($table, $rows);
		}
	}

	public function perform($query, array $params = [])
	{
		return $query;
	}

	public function numRows($res = null)
	{
		if (is_string($res)) {
			debug($res);

		}
	}

	public function affectedRows($res = null)
	{
		debug(__METHOD__);
	}

	public function getTables()
	{
		return array_keys($this->data);
	}

	public function lastInsertID($res, $table = null)
	{
		debug(__METHOD__);
	}

	public function free($res)
	{
		debug(__METHOD__);
	}

	public function quoteKey($key)
	{
		return $key;
	}

	public function quoteKeys(array $keys)
	{
		return $keys;
	}

	public function escapeBool($value)
	{
		debug(__METHOD__);
	}

	public function fetchAssoc($res)
	{
		debug(__METHOD__);
	}

	public function transaction()
	{
		debug(__METHOD__);
	}

	public function commit()
	{
		debug(__METHOD__);
	}

	public function rollback()
	{
		debug(__METHOD__);
	}

	public function getScheme()
	{
		debug(__METHOD__);
	}

	public function getTablesEx()
	{
		debug(__METHOD__);
	}

	public function getTableColumnsEx($table)
	{
		debug(__METHOD__);
	}

	public function getIndexesFrom($table)
	{
		debug(__METHOD__);
	}

	public function dataSeek($resource, $index)
	{
		debug(__METHOD__);
	}

	public function escape($string)
	{
		debug(__METHOD__);
	}

	public function fetchAll($res_or_query, $index_by_key = null)
	{
		if ($res_or_query instanceof SQLSelectQuery) {
			$table = first($res_or_query->getFrom()->getAll());
			return $this->data[$table];
		} else {
			//throw new NotImplementedException(__METHOD__);
			debug($res_or_query);
		}
	}

	public function isConnected()
	{
		return true;
	}

	public function runInsertQuery($table, array $data)
	{
//		debug('runInsertQuery', sizeof($this->data[$table]));
		$this->data[$table][] = $data;
//		debug('runInsertQuery', sizeof($this->data[$table]));
	}

	public function runUpdateQuery($table, array $set, array $where)
	{
		$data = ArrayPlus::create($this->data[$table]);
		$data->filterBy($where);
		foreach ($data as $key => $row) {
			$this->data[$table][$key] = array_merge($this->data[$table][$key], $set);
		}
	}

	public function getSelectQuery($table, array $where, $orderBy = null)
	{
//		return SQLSelectQuery::getSelectQueryP($this, $table, $where, $orderBy);
		throw new \RuntimeException('Not implemented');
	}

	public function getSelectQuerySW($table, SQLWhere $where, $orderBy = '', $addSelect = '')
	{
//		return SQLSelectQuery::getSelectQueryP($this, $table, $where->getAsArray(), $orderBy);
		throw new \RuntimeException('Not implemented');
	}

	public function getCount(SQLSelectQuery $query)
	{
		$table = first($query->getFrom()->getAll());
		$where = $query->getWhere();
		if ($where->getAsArray()) {
			//throw new NotImplementedException(__METHOD__);
		}
		return count($this->data[$table]);
	}

	public function fetchOneSelectQuery($table, array $where)
	{
		$data = ArrayPlus::create($this->data[$table]);
		$data->filterBy($where);
		return $data->count() ? $data->first() : null;
	}

	public function fetchAllSelectQuery($table, array $where = [])
	{
		$rows = $this->data[$table];
//		debug($table, $rows);
		if (!is_array($rows)) {
			$rows = [];
		}
		$data = ArrayPlus::create($rows);
		$data->filterBy($where);
		return $data;
	}

	public function createTable($table)
	{
		if (!isset($this->data[$table])) {
			$this->data[$table] = [];
		}
	}

	public function getRowsIn($table)
	{
		return count(ifsetor($this->data[$table], []));
	}

	public function hasData()
	{
		$totalRows = array_reduce($this->data, function ($acc, array $rows) {
			return $acc + sizeof($rows);
		}, 0);
		return $totalRows;
	}

	/**
	 * Don't set the whole data to [] because in this case session will not be updated.
	 */
	public function clearAll()
	{
		foreach ($this->data as $table => $_) {
			$this->data[$table] = [];
		}
	}

	public function quoteSQL($value, $key = null)
	{
		// TODO: Implement quoteSQL() method.
	}

	public function clearQueryLog()
	{
		// TODO: Implement clearQueryLog() method.
	}

	public function getLastQuery()
	{
		// TODO: Implement getLastQuery() method.
	}

	/** @return string */
	public function getDSN()
	{
		return '';
	}

	public function getInfo()
	{
		return ['class' => get_class($this)];
	}

	public function getDatabaseName()
	{
		return get_class($this);
	}

	public function getVersion()
	{
		// TODO: Implement getVersion() method.
	}

	public function __call($name, $arguments)
	{
		// TODO: Implement @method  runDeleteQuery($table, array $where)
	}
}
