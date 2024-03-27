<?php

/**
 * Class MySQLi
 * Should work but it doesn't get num_rows() after store_result().
 * @method  getSelectQuery($table, array $where = [], $order = '', $addSelect = '')
 * @method  runSelectQuery($table, array $where = [], $order = '', $addSelect = '')
 * @method  runDeleteQuery($table, array $where)
 */
class DBLayerMySQLi extends DBLayerBase implements DBInterface
{

	/**
	 * @var MySQLi
	 */
	public $connection;

	/**
	 * @var array
	 */
	public $columns = [];

	public function __construct($db = null, $host = '127.0.0.1', $login = 'root', $password = '')
	{
		$this->database = $db;
		if ($this->database) {
			$this->connect($host, $login, $password);
		}
	}

	public function connect($host, $login, $password)
	{
		$this->connection = new mysqli($host, $login, $password, $this->database);
		if (!$this->connection) {
			throw new Exception(mysqli_error($this->connection), mysqli_errno($this->connection));
		}
		$this->connection->set_charset('utf8');
	}

	/**
	 * @param resource $res
	 * @return array|false
	 * @throws DatabaseException
	 */
	public function fetchAssoc($res)
	{
		//		debug(gettype2($res));
		if ($res instanceof mysqli_result) {
			$data = (array)$res->fetch_assoc();
			//			debug(gettype2($res), $data);
			return $data;
		} elseif (is_string($res)) {
			$res = $this->perform($res);
			return $res->fetch_assoc();
		} elseif ($res instanceof SQLSelectQuery) {
			$res = $this->perform($res . '', $res->getParameters());
			return $res->fetch_assoc();
		} elseif ($res instanceof mysqli_stmt) {
			$res->fetch();
			return $this->columns;
		} else {
			debug(typ($res));
			throw new InvalidArgumentException(__METHOD__);
		}
	}

	/**
	 * @param       $query
	 * @param array $params
	 * @return bool|mysqli_result
	 * @throws DatabaseException
	 */
	public function perform($query, array $params = [])
	{
		$this->lastQuery = $query;

		if ($params) {
			$stmt = $this->prepare($query);
			if ($stmt) {
				$types = str_repeat('s', sizeof($params));
				//			debug($types, $params, $query.'');
				call_user_func_array(
					[$stmt, 'bind_param'],
					array_merge(
						[$types],
						$this->makeValuesReferenced($params)
					)
				);
				$stmt->execute();
				$stmt->store_result();
				debug($stmt);
				if (method_exists($stmt, 'get_result')) {
					$ok = $stmt->get_result();
				} else {
					$meta = $stmt->result_metadata();
					$data = [];
					while ($field = $meta->fetch_field()) {
						//						debug($field);
						$this->columns[$field->name] = &$data[$field->name];
						// pass by reference
					}
					//debug($data, $meta, $field, $this->columns);
					$ok = call_user_func_array([$stmt, 'bind_result'], $this->columns);
				}
				if (!$ok) {
					throw new DatabaseException(mysqli_error($this->connection));
				}
			} else {
				throw new DatabaseException(mysqli_error($this->connection));
			}
			//debug($query, $params, $stmt->num_rows);
		} else {
			$stmt = $this->connection->query($query);
			//			$stmt->fetch_assoc();
		}

		if (!$stmt) {
			debug($query . '', $params);
			throw new DatabaseException($this->connection->error, $this->connection->errno);
		}
		return $stmt;
	}

	public function prepare($sql)
	{
		return mysqli_prepare($this->connection, $sql);
	}

	private function makeValuesReferenced(array $arr)
	{
		$refs = [];
		foreach ($arr as $key => $value) {
			$refs[$key] = &$arr[$key];
		}
		return $refs;
	}

	public function affectedRows($res = null)
	{
		return $this->connection->affected_rows;
	}

	public function escape($string)
	{
		return $this->connection->escape_string($string);
	}

	public function escapeBool($value)
	{
		return intval(!!$value);
	}

	/**
	 * @param resource $res
	 * @param null $table
	 * @return mixed
	 */
	public function lastInsertID($res, $table = null)
	{
		return $this->connection->insert_id;
	}

	/**
	 * @param mysqli_result $res
	 * @return mixed
	 */
	public function numRows($res = null)
	{
		//debug($res->num_rows);
		return $res->num_rows;
	}

	public function getPlaceholder($field)
	{
		//		$slug = ?URL::getSlug($field);
		//		$slug = str_replace('-', '_', $slug);
		//		return '@'.$slug;
		return '?';
	}

	public function getInfo()
	{
		return [
			$this->connection->host_info,
			$this->connection->server_info,
			$this->connection->client_info,
//			$this->connection->get_connection_stats(),
			$this->connection->get_charset(),
		];
	}

	/**
	 * @param string $table Table name
	 * @param array $columns array('name' => 'John', 'lastname' => 'Doe')
	 * @return string
	 */
	public function getReplaceQuery($table, $columns)
	{
		$fields = implode(", ", $this->quoteKeys(array_keys($columns)));
		$values = implode(", ", $this->quoteValues(array_values($columns)));
		$table = $this->quoteKey($table);
		$q = "REPLACE INTO {$table} ({$fields}) VALUES ({$values})";
		return $q;
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
