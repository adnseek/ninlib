<?php

/**
 * Class dbLayerODBC
 * @mixin SQLBuilder
 * @method  getSelectQuery($table, array $where = [], $order = '', $addSelect = '')
 * @method  runSelectQuery($table, array $where = [], $order = '', $addSelect = '')
 * @method  runDeleteQuery($table, array $where)
 */
class DBLayerODBC extends DBLayerBase implements DBInterface
{

	/**
	 * @var resource
	 */
	public $connection;

	/**
	 * @var resource
	 */
	public $result;

	public $cursor = null;

	public function __construct($user, $password, $host, $db)
	{
		if ($user) {
			$this->connect($user, $password, $host, $db);
			$this->setQB();
		}
	}

	public function connect($user, $password, $host, $db)
	{
		//$this->connection = odbc_connect('odbc:DRIVER={IBM DB2 ODBC DRIVER};HOSTNAME='.$host.';PORT=50000;DATABASE=PCTRANSW;PROTOCOL=TCPIP', $user, $password);
		$this->connection = odbc_pconnect('DSN=' . $host . ';DATABASE=' . $db, $user, $password);
	}

	public function perform($query, array $params = [])
	{
		if ($this->connection) {
			$this->result = odbc_exec($this->connection, $query);
			if (!$this->result) {
				throw new Exception($this->lastError());
			}
		} else {
			throw new Exception($this->lastError());
		}
		return $this->result;
	}

	public function lastError()
	{
		return 'ODBC error #' . odbc_error() . ': ' . odbc_errormsg();
	}

	public function numRows($res = null)
	{
		return odbc_num_rows($res);
	}

	public function affectedRows($res = null)
	{
		// TODO: Implement affectedRows() method.
	}

	public function getTables()
	{
		$this->result = odbc_tables($this->connection);
		return $this->fetchAll($this->result);
	}

	public function escapeBool($value)
	{
		return !!$value;
	}

	public function free($res)
	{
		if (is_resource($res)) {
			odbc_free_result($res);
		}
	}

	public function lastInsertID($result, $key = null)
	{
		// TODO: Implement lastInsertID() method.
	}

	public function quoteKey($key)
	{
		return $key;
	}

	public function dataSeek($res, $set)
	{
		$this->cursor = $set;
	}

	public function fetchAssoc($res)
	{
		if ($this->connection) {
			if (is_null($this->cursor)) {
				$row = odbc_fetch_array($res);
			} else {
				// row numbering starts with 1 - @see php.net
				$row = odbc_fetch_array($res, 1 + $this->cursor++);
			}
			//debug(__METHOD__, $this->cursor, $row);
			return $row;
		}

		throw new RuntimeException(__METHOD__);
	}

	public function getVersion()
	{
		// TODO: Implement getVersion() method.
	}

	public function __call($method, array $params)
	{
		// TODO: Implement @method  getSelectQuery($table, array $where = [], $order = '', $addSelect = '')
		// TODO: Implement @method  runSelectQuery($table, array $where = [], $order = '', $addSelect = '')
		// TODO: Implement @method  runDeleteQuery($table, array $where)
	}
}
