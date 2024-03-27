<?php

/**
 * Class SQLBuilder - contains database unspecific (general) SQL functions.
 * It has $this->db a database specific (PostgreSQL, MySQL, SQLite, Oracle, PDO) class
 * which is performing the actual queries.
 * This $db class has a back reference to this as $this->db->qb == $this.
 * Usage in controllers/models:
 * $this->db = new MySQL();
 * $this->db->qb = new SQLBuilder();
 * $this->db->qb->db = $this->db;
 * $this->db->fetchSelectQuery(...);
 *
 * Note that the creation of objects above is handled by DIContainer
 * but it's not shown above for comprehensibility.
 * @mixin DBLayerBase
 * @method describeView($viewName)
 * @method getFirstValue($query)
 * @method performWithParams($query, $params)
 * @method getInfo()
 * @method getConnection()
 * @method getViews()
 * @method getScheme()
 * @method quoteKeys(array $keys)
 * @method quoteKey($key)
 * @method perform($query, array $params = [])
 * @method fetchAssoc($res)
 */
class SQLBuilder
{

	/**
	 * Update/Insert is storing the found row for debugging
	 * @var mixed
	 */
	public $found;

	/**
	 * @var DBInterface
	 */
	public $db;

	/**
	 * @var Config
	 */
	public $config;

	public $logToLog = false;

	public function __construct(DBInterface $db)
	{
		if (class_exists('Config')) {
			$this->config = Config::getInstance();
		}
		$this->db = $db;
	}

	/**
	 * 2010/09/12: modified according to mantis request 0001812    - 4th argument added
	 * @param $array
	 * @param $field
	 * @param string $joiner
	 * @param string $conditioner
	 * @return string
	 */
	public static function array_intersect($array, $field, $joiner = 'OR', $conditioner = 'ANY')
	{
		//$res[] = "(string_to_array('".implode(',', $value)."', ',')) <@ (string_to_array(bug.".$field.", ','))";
		// why didn't it work and is commented?

		//debug($array);
		if (sizeof($array)) {
			$or = [];
			foreach ($array as $langID) {
				//2010/09/12: modified according to mantis request 0001812	- if/else condition for 4th argument added
				if ($conditioner === 'ANY') {
					$or[] = "'" . $langID . "' = ANY(string_to_array(" . $field . ", ','))"; // this line is the original one
				} else {
					$or[] = "'" . $langID . "' = " . $field . " ";
				}
			}
			$content = '(' . implode(' ' . $joiner . ' ', $or) . ')';
		} else {
			$content = ' 1 = 1 ';
		}
		return $content;
	}

	/**
	 * @param $table
	 * @param array $where
	 * @param string $order
	 * @param string $addSelect
	 * @return string
	 * @throws Exception
	 * @throws MustBeStringException
	 */
	public function getSelectQueryString($table, array $where = [], $order = "", $addSelect = '')
	{
		$table1 = $this->getFirstWord($table);
		if ($table == $table1) {
			$from = $this->db->quoteKey($table);    // table name always quoted
		} else {
			$from = $table; // not quoted
		}
		$select = $addSelect ? $addSelect : $this->quoteKey($table1) . ".*";
		$q = "SELECT $select\nFROM " . $from;
		$set = $this->quoteWhere($where);
		if (sizeof($set)) {
			$q .= "\nWHERE\n" . implode("\nAND ", $set);
		}
		$q .= "\n" . $order;
		return $q;
	}

	public static function getFirstWord($table)
	{
		if (!$table) {
			throw new InvalidArgumentException(__METHOD__ . ' called on [' . $table . ']');
		}
		$table1 = trimExplode(' ', $table);
		$table0 = $table1[0];
		$table1 = trimExplode("\t", $table0);
		$table0 = $table1[0];
		$table1 = trimExplode("\n", $table0);
		$table0 = $table1[0];
		//debug($table, $table1, $table0);
		return $table0;
	}

	/**
	 * Quotes the values as quoteValues does, but also puts the key out and the correct comparison.
	 * In other words, it takes care of col = 'NULL' situation and makes it 'col IS NULL'
	 *
	 * @param array $where
	 * @return array
	 * @throws MustBeStringException
	 * @throws Exception
	 */
	public function quoteWhere(array $where)
	{
		$set = [];
		foreach ($where as $key => $val) {
			$endsWithDot = is_string($key) && $key[strlen($key) - 1] !== '.';
			if (!is_string($key) || $endsWithDot) {
				$equal = new SQLWhereEqual($key, $val);
				$equal->injectDB($this->db);
				$set[] = $equal->__toString();
			}
		}
		//debug($set);
		return $set;
	}

	public function getDefaultInsertFields()
	{
		return [];
	}

	public function runSelectQuerySW($table, SQLWhere $where, $order = '', $addSelect = '')
	{
		$query = $this->getSelectQuerySW($table, $where, $order, $addSelect);
		//debug($query);
		return $this->db->perform($query);
	}

	public function getSelectQuerySW($table, SQLWhere $where, $order = "", $addSelect = '')
	{
		$table1 = $this->getFirstWord($table);
		$select = $addSelect ?: $this->quoteKey($table1) . ".*";
		return $this->getSelectQuery($table, $where, $order, $select);
	}

	public function getSelectQuery($table, $where = [], $orderAndLimit = '', $addSelect = null)
	{
		$sqlWhere = $where instanceof SQLWhere ? $where : new SQLWhere($where);
		if (strpos($orderAndLimit, 'LIMIT') > 0) {  // ORDER BY xxx LIMIT yyy
			[$order, $limit] = explode('LIMIT', $orderAndLimit);
			$limit = 'LIMIT ' . $limit;	// fix after split
		} elseif (str_startsWith($orderAndLimit, 'ORDER')) {
			$order = $orderAndLimit;
			$limit = null;
		} elseif (str_startsWith($orderAndLimit, 'LIMIT')) {
			$order = null;
			$limit = $orderAndLimit;
		} else {
			$order = null;
			$limit = null;
		}
//		llog($orderAndLimit, '=>', $order, $limit);

		$orderBy = str_startsWith($order, 'ORDER') ? new SQLOrder($order) : null;
		$limit = str_startsWith($limit, 'LIMIT') ? new SQLLimit(
			str_replace('LIMIT', '', $limit)
		) : null;

		return new SQLSelectQuery($this->db, new SQLSelect($addSelect ?? '*'), new SQLFrom($table), $sqlWhere, null, null, null, $orderBy, $limit);
	}

	/**
	 * Will search for $where and then either
	 * - update $fields + $where or
	 * - insert $fields + $where + $insert
	 * @param $table
	 * @param array $fields
	 * @param array $where
	 * @param array $insert
	 * @return bool|int
	 * @throws MustBeStringException
	 */
	public function runInsertUpdateQuery($table, array $fields, array $where, array $insert = [])
	{
		TaylorProfiler::start(__METHOD__);
		$this->db->transaction();
		$res = $this->runSelectQuery($table, $where);
		$this->found = $this->fetchAssoc($res);
		if ($this->db->numRows($res)) {
			$query = $this->getUpdateQuery($table, $fields, $where);
			$this->perform($query);
			$inserted = $this->found['id'];
		} else {
			$query = $this->getInsertQuery($table, $fields + $where + $insert);
			// array('ctime' => NULL) # TODO: make it manually now
			$res = $this->perform($query);
			$inserted = $this->db->lastInsertID($res, $table);
		}
		//debug($query);
		$this->db->commit();
		$this->lastQuery = $query;  // overwrite 'commit'
		TaylorProfiler::stop(__METHOD__);
		return $inserted;
	}

	public function runSelectQuery($table, array $where = [], $order = '', $addSelect = '')
	{
		$query = $this->getSelectQuery($table, $where, $order, $addSelect);
		//debug($query);
		return $this->db->perform($query);
	}

	/**
	 * @param string $table
	 * @param array $columns
	 * @param array $where
	 * @param string $orderBy
	 * @return string
	 * @throws MustBeStringException
	 */
	public function getUpdateQuery($table, $columns, $where, $orderBy = '')
	{
		//$columns['mtime'] = date('Y-m-d H:i:s');
		$table = $this->quoteKey($table);
		$q = "UPDATE $table\nSET ";
		$set = $this->quoteLike($columns, '$key = $val');
		$q .= implode(",\n", $set);
		$q .= "\nWHERE\n";
		$q .= implode("\nAND ", $this->quoteWhere($where));
		$q .= ' ' . $orderBy;
		return $q;
	}

	/**
	 *
	 * @param $columns [a => b, c => d]
	 * @param $like "$key ILIKE '%$val%'"
	 * @return array    [a ILIKE '%b%', c ILIKE '%d%']
	 * @throws MustBeStringException
	 */
	public function quoteLike($columns, $like)
	{
		$set = [];
		foreach ($columns as $key => $val) {
			$key = $this->quoteKey($key);
			$val = $this->quoteSQL($val, $key);
			$from = ['$key', '$val'];
			$to = [$key, $val];
			$set[] = str_replace($from, $to, $like);
		}
		//d($_POST, $_REQUEST, $columns, $set, ini_get('magic_quotes_gpc'), get_magic_quotes_gpc(), get_magic_quotes_runtime());
		return $set;
	}

	/**
	 * Used to really quote different values so that they can be attached to "field = "
	 *
	 * @param mixed $value
	 * @param string $key
	 * @return string
	 * @throws MustBeStringException
	 */
	public function quoteSQL($value, $key = null)
	{
		if ($value instanceof SQLNow) {     // check subclass first
			$value->injectDB($this->db);
			return $value . '';
		} elseif ($value instanceof AsIsOp) {     // check subclass first
			$value->injectDB($this->db);
			$value->injectField($key);
			$result = $value->__toString();
			return $result;
		} elseif ($value instanceof AsIs) {
			$value->injectDB($this->db);
			//$value->injectField($key); not needed as it will make the field name twice
			return $value->__toString();
		} elseif ($value instanceof SQLOr) {
			return $value->__toString();
		} elseif ($value instanceof Time) {
			return $this->quoteSQL($value->getMySQL(), $key);
		} elseif ($value instanceof SQLDate) {
			$content = "'" . $this->db->escape($value->__toString()) . "'";
			//debug($content, $value);
			return $content;
		} elseif ($value instanceof Time) {
			$content = "'" . $this->db->escape($value->toSQL()) . "'";
			//debug($content);
			return $content;
		} elseif ($value instanceof SimpleXMLElement && $this->getScheme() == 'mysql') {
			return "COMPRESS('" . $this->db->escape($value->asXML()) . "')";
		} elseif (is_object($value)) {
			if ($value instanceof stdClass) {
				debug($value);
			}
			return "'" . $this->db->escape((string)$value) . "'";
		} elseif ($value === null) {
			return "NULL";
		} elseif (is_numeric($value) && !$this->isExp($value)) {
			//$set[] = "($key = ".$val." OR {$key} = '".$val."')";
			return "'" . $value . "'";    // quoting will not hurt, but will keep leading zeroes if necessary
			// /* numeric */";		// this makes SQLQuery not work
		} elseif (is_bool($value)) {
			$res = $this->db->escapeBool($value);
			//debug($value, $key, get_class($this->db), $res);
			return $res;
		} elseif (is_scalar($value)) {
			$sql = "'" . $this->db->escape($value) . "'";
			if ($this->db->getScheme() == 'ms') {
				$sql = 'N' . $sql;    // UTF-8 encoding
			}
			return $sql;
		} else {
			debug([
				'key' => $key,
				'value' => $value,
				'problem' => 'MustBeStringException',
			]);
			throw new MustBeStringException('Must be string.');
		}
	}

	/**
	 * http://stackoverflow.com/a/4964120
	 * @param $number
	 * @return bool
	 */
	public function isExp($number)
	{
		return is_numeric($number) && $number != number_format($number, 0, '', '');
	}

	/**
	 * @param string $table Table name
	 * @param array $columns array('name' => 'John', 'lastname' => 'Doe')
	 * @param array $where
	 * @return string
	 * @throws MustBeStringException
	 */
	public function getInsertQuery($table, array $columns, array $where = [])
	{
		$fields = implode(", ", $this->quoteKeys(array_keys($columns)));
		$values = implode(", ", $this->quoteValues(array_values($columns)));
		$table = $this->quoteKey($table);
		$q = "INSERT INTO {$table} ({$fields}) ";
		if ($where) {
			$q .= "SELECT $values ";
			$q .= 'WHERE ' . implode(' AND ', $this->quoteWhere($where));
		} else {
			$q .= "VALUES ({$values})";
		}
		return $q;
	}

	/**
	 * Quotes the complete array if necessary.
	 *
	 * @param array $a
	 * @return array
	 * @throws MustBeStringException
	 */
	public function quoteValues(array $a)
	{
		//		debug(__METHOD__, $a);
		$c = [];
		foreach ($a as $key => $b) {
			$c[] = $this->quoteSQL($b, $key);
		}
		return $c;
	}

	/**
	 * Inserts only if not yet found.
	 *
	 * @param $table
	 * @param array $fields
	 * @param array $insert
	 * @return resource
	 * @throws Exception
	 */
	public function runInsertNew($table, array $fields, array $insert = [])
	{
		TaylorProfiler::start(__METHOD__);
		$resInsert = null;
		$res = $this->runSelectQuery($table, $fields);
		if (!$this->db->numRows($res)) {
			$query = $this->getInsertQuery($table, $fields + $insert);
			//debug($query);
			$resInsert = $this->db->perform($query);
		}
		TaylorProfiler::stop(__METHOD__);
		return $resInsert;
	}

	public function runReplaceQuery($table, array $columns, array $primaryKeys)
	{
		TaylorProfiler::start(__METHOD__ . '(' . $table . ')');
		$ret = $this->db->runReplaceQuery($table, $columns, $primaryKeys);
		TaylorProfiler::stop(__METHOD__ . '(' . $table . ')');
		return $ret;
	}

	public function getFoundOrLastID($inserted)
	{
		if ($inserted) {
			$authorID = $this->db->lastInsertID($inserted);
		} else {
			$authorID = $this->found['id'];
		}
		return $authorID;
	}

	/**
	 * Return ALL rows
	 * This used to retrieve a single row !!!
	 * @param string $table
	 * @param array $where
	 * @param string $order
	 * @param string $addFields
	 * @param string $idField - will return data as assoc indexed by this column
	 * @return array
	 */
	public function fetchSelectQuery($table, $where = [], $order = '', $addFields = '', $idField = null)
	{
		// commented to allow working with multiple MySQL objects (SQLBuilder instance contains only one)
		//$res = $this->runSelectQuery($table, $where, $order, $addFields);
		$query = $this->getSelectQuery($table, $where, $order, $addFields);

		//debug($query); if ($_COOKIE['debug']) { exit(); }

		$res = $this->perform($query);
		return $this->fetchAll($res, $idField);
	}

	/**
	 * @param resource|string $res
	 * @param string $key can be set to NULL to avoid assoc array
	 * @return array
	 */
	public function fetchAll($res, $key = null)
	{
		TaylorProfiler::start(__METHOD__);
		if (is_string($res) || $res instanceof SQLSelectQuery) {
			$res = $this->db->perform($res);
		}

		$data = [];
		do {
			$row = $this->db->fetchAssoc($res);
			if ($row === false || $row == [] || $row === null) {
				break;
			}
			if ($key) {
				if (!isset($row[$key])) {
					debug($key, $row);
				}
				$keyValue = $row[$key];
				$data[$keyValue] = $row;
			} else {
				$data[] = $row;
			}
		} while (true);
		//debug($this->lastQuery, sizeof($data));
		//debug_pre_print_backtrace();
		$this->db->free($res);
		TaylorProfiler::stop(__METHOD__);
		return $data;
	}

	/**
	 * Originates from BBMM
	 * @param string $sword
	 * @param array $fields
	 * @return array
	 */
	public function getSearchWhere($sword, array $fields)
	{
		$where = [];
		$words = $this->getSplitWords($sword);
		foreach ($words as $word) {
			$like = [];
			foreach ($fields as $field) {
				$like[] = $field . " LIKE '%" . $this->db->escape($word) . "%'";
			}
			$where[] = new AsIsOp(' (' . implode(' OR ', $like) . ')');
		}
		//debug($where);
		return $where;
	}

	public function getSplitWords($sword)
	{
		$sword = trim($sword);
		$words = explode(' ', $sword);
		$words = array_map('trim', $words);
		$words = array_filter($words);
		$words = array_unique($words);
		//$words = $this->combineSplitTags($words);
		$words = array_values($words);
		return $words;
	}

	public function combineSplitTags($words)
	{
		$new = [];
		$i = 0;
		$in = false;
		foreach ($words as $word) {
			$word = new StringPlus($word);
			if ($word->contains('[')) {
				++$i;
				$in = true;
			}
			$new[$i] = $new[$i] ? $new[$i] . ' ' . $word : $word . '';
			if (!$in || ($in && $word->contains(']'))) {
				++$i;
				$in = false;
			}
		}
		//debug(array($words, $new));
		return $new;
	}

	public function runDeleteQuery($table, array $where)
	{
		$delete = $this->getDeleteQuery($table, $where);
		$w = new SQLWhere($where);
		$params = $w->getParameters();
		$delete = $w->replaceParams($delete);
		//		debug($delete, $params);
		return $this->db->perform($delete, $params);
	}

	/**
	 * @param string $table
	 * @param array $where
	 * @param string $what [LOW_PRIORITY] [QUICK] [IGNORE]
	 * @return string
	 * @throws MustBeStringException
	 * @throws Exception
	 */
	public function getDeleteQuery($table, array $where = [], $what = '')
	{
		$q = "DELETE " . $what . " FROM " . $this->db->quoteKey($table) . " ";
		$set = $this->quoteWhere($where);
		if (sizeof($set)) {
			$q .= "\nWHERE " . implode(" AND ", $set);
		} else {
			$q .= "\nWHERE 1 = 0"; // avoid truncate()
		}
		return $q;
	}

	public function __call($method, array $params)
	{
		return call_user_func_array([$this->getDB(), $method], $params);
	}

	public function getDB()
	{
		return $this->db = $this->db ?: $this->config->getDB();
	}

	public function getTableOptions($table, $titleField, $where = [], $order = null, $idField = 'id', $prefix = null)
	{
		$prefix = $prefix ?: $table . '.';

		if (!str_contains($titleField, ' AS ')) {
			$addSelect = 'DISTINCT ' . $prefix . $this->quoteKey($titleField) . ' AS title,';
		} else {
			$addSelect = $titleField;
		}

		$query = $this->getSelectQuery(
			$table,
			$where,
			$order,
			$addSelect . ' ' .
			$this->quoteKey($prefix . $idField) . ' AS id_field'
		);

		// $prefix.'*, is not selected as DISTINCT will not work

		//debug('Query', $query.''); exit();
		$res = $this->perform($query);
		$data = $this->fetchAll($res, 'id_field');
		$keys = array_keys($data);
		$values = array_map(function ($arr) {
			return $arr["title"];
		}, $data);
		//d($keys, $values);
		if ($keys && $values) {
			$options = array_combine($keys, $values);
		} else {
			$options = [];
		}
		//debug($this->db->lastQuery, @$this->db->numRows($res), $titleField, $idField, $data, $options);
		//		$options = AP($data)->column_assoc($idField, $titleField)->getData();
		return $options;
	}

	/**
	 * @param string $query
	 * @param string|null $className - if provided it will return DatabaseInstanceIterator
	 * @return DatabaseInstanceIterator|DatabaseResultIteratorAssoc
	 * @throws DatabaseException
	 */
	public function getIterator($query, $className = null)
	{
		if ($className) {
			$f = new DatabaseInstanceIterator($this->db, $className);
			if (is_string($query)) {
				$f->perform($query);
			} else {
				$f->setResult($query);
			}
			return $f;
		} elseif ($this->db instanceof DBLayerPDO) {
			$res = $this->db->perform($query);
			return $res;
		} elseif (is_string($query)) {
			$f = new DatabaseResultIteratorAssoc($this->db);
			$f->perform($query);
			return $f;
		} elseif (is_resource($query)) {
			$f = new DatabaseResultIteratorAssoc($this->db);
			$f->setResult($query);
			return $f;
		} else {
			throw new InvalidArgumentException(__METHOD__ . ' __/(:-)\__ ' . $query);
		}
	}

	public function fetchOneSelectQuery($table, $where = [], $order = '', $selectPlus = '')
	{
		$query = $this->getSelectQuery($table, $where, $order, $selectPlus);
		if ($this->logToLog) {
			llog($query . '', $query->getParameters(), get_class($this->db), $this->db->getConnection());
		}
		$res = $this->db->perform($query, $query->getParameters());
		if ($this->logToLog) {
			llog('$res', $res);
		}
		$data = $this->db->fetchAssoc($res);
		return $data;
	}

	public function runUpdateInsert($table, $set, $where)
	{
		$found = $this->runSelectQuery($table, $where);
		if ($this->db->numRows($found)) {
			$res = 'update';
			$this->runUpdateQuery($table, $set, $where);
		} else {
			$res = 'insert';
			$this->runInsertQuery($table, $set + $where);
		}
		return $res;
	}

	public function runUpdateQuery($table, array $columns, array $where, $orderBy = '')
	{
		$query = $this->getUpdateQuery($table, $columns, $where, $orderBy);
		return $this->db->perform($query);
	}

	public function runInsertQuery($table, array $columns, array $where = [])
	{
		TaylorProfiler::start(__METHOD__ . '(' . $table . ')');
		$query = $this->getInsertQuery($table, $columns, $where);
		$ret = $this->db->perform($query);
		TaylorProfiler::stop(__METHOD__ . '(' . $table . ')');
		return $ret;
	}

	/**
	 * @param string $table
	 * @param array $where
	 * @param string $order
	 * @param string $selectPlus
	 * @param $key
	 * @return array[]
	 */
	public function fetchAllSelectQuery($table, array $where, $order = '', $selectPlus = '', $key = null)
	{
		$res = $this->runSelectQuery($table, $where, $order, $selectPlus);
		$rows = $this->db->fetchAll($res, $key);
		return $rows;
	}

	public function getWhereString(array $where)
	{
		$set = $this->quoteWhere($where);
		return implode(' AND ', $set);
	}

	/**
	 * The query is supposed to return two columns only
	 * @param $query
	 * @return array
	 */
	public function fetchOptions($query)
	{
		$data = [];
		if (is_string($query) || $query instanceof SQLSelectQuery) {
			$result = $this->perform($query);
		} else {
			$result = $query;
		}
		$row = $this->fetchAssoc($result);
		while ($row != false && $row != null) {
			[$key, $val] = array_values($row);
			$data[$key] = $val;

			$row = $this->fetchAssoc($result);
		}
		return $data;
	}

	public function getCount(SQLSelectQuery $query)
	{
		$queryWithoutOrder = clone $query;
		$queryWithoutOrder->unsetOrder();

		$subQuery = new SQLSubquery($queryWithoutOrder, 'counted');
		$subQuery->setParameters($query->getParameters());
		$query = new SQLSelectQuery($this->db,
			new SQLSelect('count(*) AS count'),
			$subQuery
		);
		$query->injectDB($this->db);

		$res = $query->fetchAssoc();
		//		debug($res);
		return ifsetor($res['count']);
	}

	public function getReserved()
	{
		if ($this->db instanceof DBLayerPDO) {
			if ($this->db->isMySQL()) {
				return [];
			}

			if ($this->db->isPostgres()) {
				return (new DBLayer())->getReserved();
			}

			return [];
		}

		return [];
	}
}
