<?php

/**
 * Class dbLayerPDO
 * @mixin SQLBuilder
 * @method runSelectQuery($table, array $where = [], $order = '', $addSelect = '')
 */
class DBLayerPDO extends DBLayerBase implements DBInterface
{

	/**
	 * @var PDO
	 */
	public $connection;

	/**
	 * @var PDOStatement
	 */
	public $lastResult;

	/**
	 * @var string
	 */
	public $dsn;

	/**
	 * @var string
	 */
	public $lastQuery;

	/**
	 * @var null|int
	 */
	protected $dataSeek = null;

	protected $host, $user, $password, $db;

	public function __construct($db = null, $host = null,
															$user = null, $password = null,
															$scheme = 'mysql', $driver = null,
															$port = 3306)
	{
		if ($user) {
			$this->host = $host;
			$this->user = $user;
			$this->password = $password;
			$this->db = $db;
			$this->connect($user, $password,
				$scheme, $driver,
				$host, $db, $port);
		}

		if (DEVELOPMENT) {
			$this->queryLog = new QueryLog();
		}
		//$this->setQB(); // must be injected outside (inf loop)
//		debug_pre_print_backtrace();
	}

	/**
	 * @param string $user
	 * @param string $password
	 * @param string $scheme
	 * @param string $driver string IBM DB2 ODBC DRIVER
	 * @param string $host
	 * @param string $db
	 * @param int $port
	 */
	public function connect($user, $password, $scheme, $driver, $host, $db, $port = 3306)
	{
		//$dsn = $scheme.':DRIVER={'.$driver.'};DATABASE='.$db.';SYSTEM='.$host.';dbname='.$db.';HOSTNAME='.$host.';PORT='.$port.';PROTOCOL=TCPIP;';
		if ($scheme === 'sqlite') {
			$this->database = basename($db);
		} else {
			$this->database = $db;
		}

		$builder = DSNBuilder::make($scheme, $host, $user, $password, $db, $port);
		if ($driver) {
			$builder->setDriver($driver);
		}
		$this->dsn = $builder->__toString();
//		debug($this->dsn);
		$profiler = new Profiler();
		$this->connectDSN($this->dsn, $user, $password);
		$this->queryTime += $profiler->elapsed();
	}

	public function connectDSN($dsn, $user = null, $password = null)
	{
		$dsnParts = parse_url($dsn);
		if (!$user) {
//			debug($dsnParts);
			$user = ifsetor($dsnParts['user']);
			$password = ifsetor($dsnParts['pass']);
//			$dsn = str_replace($user.':'.$password.'@', '', $dsn);
			$dsnBuilder = DSNBuilder::make(
				$dsnParts['scheme'],
				ifsetor($dsnParts['host']),
				'',
				'',
				$dsnParts['path'],
				ifsetor($dsnParts['port'])
			);
			$dsn = $dsnBuilder->__toString();
//			debug($dsnParts);
		}
		$this->database = $dsnParts['path'];

		$this->dsn = $dsn;
		$options = [
			PDO::ATTR_PERSISTENT => false,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		];
		if ($this->isMySQL()) {
			$this->dsn .= ';charset=utf8';
			$options += [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"];
		}
		try {
			$this->connection = new PDO($this->dsn, $user, $password, $options);
			$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {
			debug([
				'class' => get_class($e),
				'exception' => $e->getMessage(),
				'dsn' => $this->dsn,
				'extensions' => get_loaded_extensions(),
			]);
			throw $e;
		}
		//$this->connection->setAttribute( PDO::ATTR_EMULATE_PREPARES, false);

		//$url = parse_url($this->dsn);
		//$this->database = basename($url['path']);

		if (0) {
			$res = $this->perform("SET NAMES 'utf8'");
			if ($res) {
				$res->closeCursor();
			}
		}
	}

	public function perform($query, array $params = [])
	{
//		echo $query, BR;
		//debug($params);
		$this->lastQuery = $query;

		$driver_options = [];
		if ($this->isMySQL()) {
			// save memory
			if ($this->lastResult) {
//				$this->lastResult->fetchAll();
//				$this->lastResult->closeCursor();
			}
//			$this->connection->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
			$driver_options[PDO::ATTR_CURSOR] = PDO::CURSOR_SCROLL;
		}

		$profiler = new Profiler();
		try {
			$this->lastResult = $this->connection->prepare($query, $driver_options);
		} catch (PDOException $e) {
//			debug($query, $params, $e->getMessage());
			$e = new DatabaseException($e->getMessage(), $query, $e);
			throw $e;
		}
		$this->queryTime += $profiler->elapsed();
		if ($this->logToLog) {
			$runTime = number_format(microtime(true) - $_SERVER['REQUEST_TIME'], 2);
			error_log($runTime . ' ' . $query);
		}
		if ($this->lastResult) {
			$profiler = new Profiler();
			try {
				$ok = $this->lastResult->execute($params);
			} catch (PDOException $e) {
				//debug($query . '', $params, $e->getMessage());
				throw $e;
			}
			$this->queryTime += $profiler->elapsed();
			if (!is_null($this->queryLog)) {
				$diffTime = $profiler->elapsed();
				$this->queryLog->log($query, $diffTime, $this->lastResult->rowCount());
			}
			if (!$ok) {
				debug([
					'class' => get_class($this),
					'ok' => $ok,
					'code' => $this->connection->errorCode(),
					'errorInfo' => $this->connection->errorInfo(),
					'query' => $query,
					'connection' => $this->connection,
					'result' => $this->lastResult,
				]);
				$e = new DatabaseException(getDebug([
					'class' => get_class($this),
					'ok' => $ok,
					'code' => $this->connection->errorCode(),
					'errorInfo' => $this->connection->errorInfo(),
					'query' => $query,
					'connection' => $this->connection,
					'result' => $this->lastResult,
				]),
					$this->connection->errorCode() ?: 0);
				$e->setQuery($query);
				throw $e;
			}
		} else {
			$e = new DatabaseException(getDebug([
				'class' => get_class($this),
				'code' => $this->connection->errorCode(),
				'errorInfo' => $this->connection->errorInfo(),
				'query' => $query,
				'connection' => $this->connection,
				'result' => $this->lastResult,
			]),
				$this->connection->errorCode() ?: 0);
			$e->setQuery($query);
			throw $e;
		}
		return $this->lastResult;
	}

	public static function getAvailableDrivers()
	{
		return PDO::getAvailableDrivers();
	}

	public function isConnected()
	{
		return !!$this->connection
			&& PGSQL_CONNECTION_OK == pg_connection_status($this->connection);
	}

	/**
	 * @param string $url
	 * @return mixed
	 * @see http://php.net/manual/de/function.parse-url.php#83828
	 */
	public function parseUrl($url)
	{
		$r = "^(?:(?P<scheme>\w+)://)?";
		$r .= "(?:(?P<login>\w+):(?P<pass>\w+)@)?";
		$r .= "(?P<host>(?:(?P<subdomain>[\w\.]+)\.)?" . "(?P<domain>\w+\.(?P<extension>\w+)))";
		$r .= "(?::(?P<port>\d+))?";
		$r .= "(?P<path>[\w/]*/(?P<file>\w+(?:\.\w+)?)?)?";
		$r .= "(?:\?(?P<arg>[\w=&]+))?";
		$r .= "(?:#(?P<anchor>\w+))?";
		$r = "!$r!";                                                // Delimiters

		preg_match($r, $url, $out);

		return $out;
	}

	/**
	 * @param PDOStatement $res
	 * @return array|mixed
	 */
	public function numRows($res = null)
	{
		$count = $res->rowCount();
		//debug($this->lastQuery, $count, $this->getScheme());
		if ($count == -1 || $this->getScheme() == 'sqlite') {
			$countQuery = 'SELECT count(*) FROM (' . $res->queryString . ') AS sub1';
			$rows = $this->fetchAll($countQuery);
			//debug($countQuery, $rows);
			$count = first(first($rows));
		}
		return $count;
	}

	public function getScheme()
	{
		$scheme = parse_url($this->dsn);
		$scheme = $scheme['scheme'];
		return $scheme;
	}

	public function fetchAll($stringOrRes, $key = null)
	{
		if (is_string($stringOrRes)) {
			$res = $this->perform($stringOrRes);
		} else {
			$res = $stringOrRes;
		}
		$data = $res->fetchAll(PDO::FETCH_ASSOC);
		//debug($this->lastQuery, $this->result, $data);

		if ($key) {
			$copy = $data;
			$data = [];
			foreach ($copy as $row) {
				$data[$row[$key]] = $row;
			}
		}
		return $data;
	}

	public function affectedRows($res = null)
	{
		return $this->lastResult->rowCount();
	}

	public function getTables()
	{
		$tables = $this->getTablesEx();
		$names = array_keys($tables);
		return $names;
	}

	/**
	 * Keys must be table names
	 * @return array|null
	 * @throws DatabaseException
	 * @throws Exception
	 */
	public function getTablesEx()
	{
		$tables = [];
		$scheme = $this->getScheme();
		if ($this->isMySQL()) {
			$res = $this->perform('show tables');
			$tables = $res->fetchAll();
			$tables = ArrayPlus::create($tables)->column('0')->getData(); // "Tables_is_DBname"
			$keys = $tables;
			foreach ($tables as &$name) {
				$name = ['table' => $name];
			}
			$tables = array_combine($keys, $tables);
		} elseif ($scheme == 'odbc') {
			$res = $this->perform('db2 list tables for all');
			$tables = $res->fetchAll();
		} elseif ($scheme == 'sqlite') {
			try {
				$file = $this->dsn;
				$file = str_replace('sqlite:', '', $file);
				$db2 = new DBLayerSQLite($file);
				$db2->connect();
				$db2->setQB(new SQLBuilder($db2)); // different DB inside
				$tables = $db2->getTablesEx();
			} catch (Exception $e) {
				throw $e;
			}
		} else {
			throw new InvalidArgumentException(__METHOD__);
		}
		return $tables;
	}

	public function setQB(SQLBuilder $qb = null)
	{
		parent::setQB($qb);
	}

	public function lastInsertID($res, $table = null)
	{
		return $this->connection->lastInsertId();
	}

	/**
	 * @param PDOStatement $res
	 */
	public function free($res)
	{
		if ($res) {
			$res->closeCursor();
		}
	}

	public function quoteKey($key)
	{
		$driver = $this->getDriver();
		return $driver->quoteKey($key);
	}

	public function uncompress($value)
	{
		return @gzuncompress(substr($value, 4));
	}

	public function transaction()
	{
		$this->perform('BEGIN');
	}

	public function commit()
	{
		return $this->perform('COMMIT');
	}

	public function rollback()
	{
		return $this->perform('ROLLBACK');
	}

	public function getTableColumns($table)
	{
		$query = "SELECT * FROM " . $table . " LIMIT 1";
		$res = $this->perform($query);
		$row = $this->fetchAssoc($res);
		$columns = array_keys($row);
		$columns = array_combine($columns, $columns);
		return $columns;
	}

	public function getIndexesFrom($table)
	{
		return [];
	}

	/**
	 * @return PDO
	 */
	public function getConnection()
	{
		return $this->connection;
	}

	public function getQb()
	{
		if (!isset($this->qb)) {
			$db = Config::getInstance()->getDB();
			$this->setQB(new SQLBuilder($db));
		}

		return $this->qb;
	}

	public function getPlaceholder()
	{
		return '?';
	}

	public function unsetQueryLog()
	{
		$this->queryLog = null;
	}

	public function getReplaceQuery($table, array $columns)
	{
		if ($this->isMySQL()) {
			$m = new DBLayerMySQLi();
			$m->qb = $this->qb;
			return $m->getReplaceQuery($table, $columns);
		}

		if ($this->isPostgres()) {
			$p = new DBLayer();
			$p->qb = $this->qb;
			return $p->getReplaceQuery($table, $columns);
		}

		throw new DatabaseException(__METHOD__ . ' is not implemented for ' . get_class($this));
	}

	public function getVersion()
	{
		return $this->getInfo()['ATTR_SERVER_VERSION'];
	}

	public function getInfo()
	{
		$info = [
			'class' => get_class($this),
			'errorInfo' => $this->connection->errorInfo(),
			'errorCode' => $this->connection->errorCode(),
		];
		$plus = [
			'ATTR_CLIENT_VERSION' => PDO::ATTR_CLIENT_VERSION,
			'ATTR_CONNECTION_STATUS' => PDO::ATTR_CONNECTION_STATUS,
			'ATTR_DRIVER_NAME' => PDO::ATTR_DRIVER_NAME,
			'ATTR_SERVER_INFO' => PDO::ATTR_SERVER_INFO,
			'ATTR_SERVER_VERSION' => PDO::ATTR_SERVER_VERSION,
			'ATTR_TIMEOUT' => PDO::ATTR_TIMEOUT,
		];
		foreach ($plus as $name => $attribute) {
			try {
				$info[$name] = $this->connection->getAttribute($attribute);
			} catch (PDOException $e) {
			}
		}
		return $info;
	}

	public function getDriver()
	{
		return 'mysql';	// maybe?
	}

}
