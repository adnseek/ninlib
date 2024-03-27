<?php

/**
 * Class dbPlacebo
 * @mixin SQLBuilder
 * @method  describeView($viewName)
 * @method  fetchAllSelectQuery($table, array $where, $order = '', $selectPlus = '', $key = null)
 * @method  getFirstValue($query)
 * @method  performWithParams($query, $params)
 * @method  getConnection()
 * @method  getViews()
 * @method  runSelectQuery($table, array $where = [], $order = '', $addSelect = '')
 * @method  runDeleteQuery($table, array $where)
 */
class DBPlacebo extends DBLayerBase implements DBInterface
{
	public $lastQuery;

	/**
	 * @var array
	 */
	protected $returnNextTime = [];

	protected $insertedRow = [];

	public function __construct()
	{
		//llog(__METHOD__, Debug::getCaller());
		// recursion:
		//$this->qb = Config::getInstance()->getQb();
	}

	public function perform($query, array $params = [])
	{
		return '';
	}

	public function fetchOptions($a)
	{
		return '';
	}

	public function __call($method, array $params)
	{
		if (method_exists($this->qb, $method)) {
			return call_user_func_array([$this->qb, $method], $params);
		} else {
//			debug(typ($this->qb));
			throw new RuntimeException($method . ' not found in dbPlacebo and SQLBuilder');
		}
	}

	public function numRows($res = null)
	{
		return count($this->returnNextTime);
	}

	public function affectedRows($res = null)
	{
		// TODO: Implement affectedRows() method.
	}

	public function getTables()
	{
		// TODO: Implement getTables() method.
	}

	public function lastInsertID($res = null, $table = null)
	{
		return rand();
	}

	public function free($res)
	{
		// TODO: Implement free() method.
	}

	public function quoteKey($key)
	{
		return '"' . $key . '"';
	}

	public function escape($string)
	{
		return $string;
	}

	public function escapeBool($value)
	{
		// TODO: Implement escapeBool() method.
	}

	public function fetchAssoc($res)
	{
		$return = $this->returnNextTime;
		$this->returnNextTime = [];
		return $return;
	}

	public function transaction()
	{
		// TODO: Implement transaction() method.
	}

	public function commit()
	{
		// TODO: Implement commit() method.
	}

	public function rollback()
	{
		// TODO: Implement rollback() method.
	}

	public function getScheme()
	{
		return get_class($this) . '://';
	}

	public function getTablesEx()
	{
		// TODO: Implement getTablesEx() method.
	}

	public function getTableColumnsEx($table)
	{
		// TODO: Implement getTableColumnsEx() method.
	}

	public function getIndexesFrom($table)
	{
		// TODO: Implement getIndexesFrom() method.
	}

	public function fetchOneSelectQuery($table, $where = [], $order = '', $selectPlus = '')
	{
		$query = $this->getSelectQuery($table, $where, $order, $selectPlus);
		$this->lastQuery = $query;
		return $this->fetchAll(null);
	}

	public function getSelectQuery($table, array $where = [], $order = '', $selectPlus = '')
	{
		$query = $this->qb->getSelectQuery($table, $where, $order, $selectPlus);
		$this->lastQuery = $query;
		return $query;
	}

	public function fetchAll($res_or_query, $index_by_key = null)
	{
		$return = $this->returnNextTime;
		//debug(__METHOD__, typ($this), $return);
		$this->returnNextTime = [];
		return $return;
	}

	public function getSelectQuerySW($table, SQLWhere $where, $order = '', $selectPlus = '')
	{
		$query = $this->getSelectQuery($table, [
			new AsIsOp($where->__toString()),
		], $order, $selectPlus);
		$this->lastQuery = $query;
		return $query;
	}

	public function getPlaceholder()
	{
		return '?';
	}

	public function getInfo()
	{
		return ['class' => get_class($this)];
	}

	public function returnNextTime(array $rows)
	{
		$this->returnNextTime = $rows;
	}

	public function runInsertQuery($table, array $columns)
	{
		if (!ifsetor($columns['id'])) {
			$columns['id'] = rand();
		}
		$this->insertedRow = $columns;
		$this->returnNextTime = $columns;
	}

	public function getFirstWord($asd)
	{
		return $asd;
	}

	public function getVersion()
	{
		// TODO: Implement getVersion() method.
	}
}
