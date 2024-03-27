<?php

/**
 * Class DBLayerBase
 * @mixin SQLBuilder
 * @method runUpdateQuery($table, array $columns, array $where, $orderBy = '')
 * @method fetchSelectQuery($table, array $where = [], $order = '', $addFields = '', $idField = null)
 * @method getInsertQuery($table, array $columns)
 * @method getDeleteQuery($table, array $where = [], $what = '')
 * @method getUpdateQuery($table, $columns, $where, $orderBy = '')
 * @method runInsertQuery($table, array $columns)
 * @method fetchOneSelectQuery($table, $where = [], $order = '', $addFields = '', $idField = null)
 * @method describeView($viewName)
 * @method fetchAllSelectQuery($table, array $where, $order = '', $selectPlus = '', $key = null)
 * @method getFirstValue($query)
 * @method performWithParams($query, $params)
 * @method getConnection()
 * @method getViews()
 */
abstract class DBLayerBase implements DBInterface
{

	/**
	 * @var SQLBuilder
	 */
	public $qb;

	public $AFFECTED_ROWS = null;

	/**
	 * List of reserved words for each DB
	 * which can't be used as field names and must be quoted
	 * @var array
	 */
	protected $reserved = [];

	/**
	 * @var resource
	 */
	public $connection;
	/**
	 * @var string
	 */
	public $lastQuery;
	/**
	 * @var int
	 */
	public $queryCount = 0;
	/**
	 * @var int Time in seconds
	 */
	public $queryTime = 0;
	/**
	 * @var string DB name (file name)
	 */
	public $database;

	/**
	 * set to NULL for disabling
	 * @var QueryLog
	 */
	protected $queryLog;
	/**
	 * @var bool Allows logging every query to the error.log.
	 * Helps to detect the reason for white screen problems.
	 */
	protected $logToLog = false;

	public function setQB(SQLBuilder $qb = null)
	{
		$this->qb = $qb;
	}

	public function __call($method, array $params)
	{
		if (!$this->qb) {
			if (!$this->qb) {
				throw new DatabaseException(__CLASS__ . ' has no QB');
			}
		}
		if (method_exists($this->qb, $method)) {
			return call_user_func_array([$this->qb, $method], $params);
		} else {
			throw new Exception($method . ' not found in ' . get_class($this) . ' and SQLBuilder');
		}
	}

	public function logQuery($query)
	{
		if ($this->logToLog) {
			$query = preg_replace('/\s+/', ' ',
				str_replace("\n", ' ', $query));
			/** @noinspection ForgottenDebugOutputInspection */
			error_log('[' . get_class($this) . ']' . TAB .
				'[' . $this->affectedRows() . ' rows]' . TAB .
				$query . ': ' . $this->queryTime);
		}
	}

	public function affectedRows($res = null)
	{
		return 0;
	}

	public function fetchPartition($res, $start, $limit)
	{
		$max = $start + $limit;
		$max = min($max, $this->numRows($res));
		$data = [];
		for ($i = $start; $i < $max; $i++) {
			$this->dataSeek($res, $i);
			$row = $this->fetchAssocSeek($res);
			if ($row !== false) {
				$data[] = $row;
			} else {
				break;
			}
		}

		// never free as one may retrieve another portion
		//$this->free($res);
		return $data;
	}

	/**
	 * @return string 'mysql', 'pg', 'ms'... PDO will override this
	 */
	public function getScheme()
	{
		return strtolower(str_replace('DBLayer', '', get_class($this)));
	}

	public function numRows($res = null)
	{
		return 0;
	}

	public function dataSeek($res, $i)
	{
	}

	public function fetchAssocSeek($res)
	{
		return null;
	}

	public function saveQueryLog($query, $time)
	{
		$this->queryCount++;
		$this->queryTime += $time;
	}

	public function transaction()
	{
		return $this->perform('BEGIN');
	}

	public function perform($query, array $params = [])
	{
		return null;
	}

	public function commit()
	{
		return $this->perform('COMMIT');
	}

	public function rollback()
	{
		return $this->perform('ROLLBACK');
	}

	public function getTables()
	{
		return [];
	}

	public function lastInsertID($res, $table = null)
	{
		return 0;
	}

	public function free($res)
	{
		// TODO: Implement free() method.
	}

	public function escapeBool($value)
	{
		return $value;
	}

	public function fetchAssoc($res)
	{
		return [];
	}

	public function getTablesEx()
	{
		return [];
	}

	public function getIndexesFrom($table)
	{
		return [];
	}

	public function isConnected()
	{
		return !!$this->connection;
	}

	public function getTableColumns($table)
	{

	}

	public function getQueryLog()
	{
		if (!$this->queryLog) {
			$this->queryLog = new QueryLog();
		}
		return $this->queryLog;
	}

	public function isMySQL()
	{
		return in_array(
			$this->getScheme(),
			['mysql', 'mysqli']);
	}

	public function isPostgres()
	{
		return $this->getScheme() == 'psql';
	}

	public function isSQLite()
	{
		return $this->getScheme() == 'sqlite';
	}

	public function clearQueryLog()
	{
		$this->queryLog = null;
	}

	public function fetchAll($res_or_query, $index_by_key = null)
	{
		// TODO: Implement fetchAll() method.
	}

	public function quoteKeys(array $a)
	{
		$c = [];
		foreach ($a as $b) {
			$c[] = $this->quoteKey($b);
		}
		return $c;
	}

	public function quoteKey($key)
	{
		$reserved = $this->getReserved();
		if (in_array(strtoupper($key), $reserved)) {
			$key = $this->db->quoteKey($key);
		}
		return $key;
	}

	public function getReserved()
	{
		return $this->reserved;
	}

	public function getDSN()
	{
//		return $this->dsn();
		return null;
	}

	/**
	 * @param string $table
	 * @param array $set
	 * @return array
	 * @throws Exception
	 */
	public function fixDataTypes($table, array $set)
	{
		$tableDesc = $this->getTableFields($table);
		foreach ($set as $key => &$val) {
			/** @var TableField $desc */
			$desc = ifsetor($tableDesc[$key]);
			if ($desc && $desc->isBoolean()) {
//				debug($desc);
				$val = boolval($val);
			} elseif ($desc && $desc->isInt()) {
//				debug($desc);
				$val = intval($val);
			} elseif ($desc && $desc->isNull() && !$val) {
				$val = null;
			}
		}
		return $set;
	}

	/**
	 * @param string $table
	 * @return TableField[]
	 * @throws Exception
	 */
	public function getTableFields($table)
	{
		$fields = $this->getTableColumnsEx($table);
		foreach ($fields as $field => &$set) {
			$set = TableField::init($set + ['pg_field' => $field]);
		}
		return $fields;
	}

	public function getTableColumnsEx($table)
	{
		return [];
	}

	public function quoteSQL($value, $key = null)
	{
		return "'" . $this->escape($value) . "'";
	}

	public function escape($string)
	{
		throw new Exception('Implement ' . __METHOD__);
	}

	public function getLastQuery()
	{
		return $this->lastQuery;
	}

	public function getInfo()
	{
		return ['class' => get_class($this)];
	}

	public function getDatabaseName()
	{
		return $this->database;
	}

}
