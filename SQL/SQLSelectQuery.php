<?php

class SQLSelectQuery extends SQLWherePart
{

	/**
	 * @var SQLJoin
	 */
	public $join;
	/**
	 * @var SQLWhere
	 */
	public $where;
	/**
	 * @var DBLayerBase|DBLayer|DBLayerPDO
	 * @protected to prevent debug output
	 */
	protected $db;
	/**
	 * @var SQLSelect
	 */
	protected $select;
	/**
	 * @var SQLFrom
	 */
	protected $from;
	/**
	 * @var  SQLGroup
	 */
	protected $group;

	/**
	 * @var SQLHaving
	 */
	protected $having;

	/**
	 * @var SQLOrder
	 */
	protected $order;

	/**
	 * @var SQLLimit
	 */
	protected $limit;

	/**
	 * SQLSelectQuery constructor.
	 * @param DBInterface $db
	 * @param SQLSelect $select
	 * @param SQLFrom $from
	 * @param SQLWhere $where
	 * @param SQLJoin $join
	 * @param SQLGroup $group
	 * @param SQLHaving $having
	 * @param SQLOrder $order
	 * @param SQLLimit $limit
	 */
	public function __construct(DBInterface $db, $select = null, $from = null, $where = null, $join = null, $group = null, $having = null, $order = null, $limit = null)
	{
		$this->db = $db;
		if ($select) {
			$this->setSelect($select);
		}
		if ($from) {
			$this->setFrom($from);
		}
		if ($where) {
			$this->setWhere($where);
		}
		if ($join) {
			$this->setJoin($join);
		} else {
			$this->join = new SQLJoin();
		}
		if ($group) {
			$this->setGroup($group);
		}
		if ($having) {
			$this->setHaving($having);
		}
		if ($order) {
			$this->setOrder($order);
		}
		if ($limit) {
			$this->setLimit($limit);
		}
	}

	public function setJoin(SQLJoin $join)
	{
		$this->join = $join;
//		$this->group->injectDB($this->db);
	}

	public function setGroup(SQLGroup $group)
	{
		$this->group = $group;
//		$this->group->injectDB($this->db);
	}

	public function setHaving(SQLHaving $having)
	{
		$this->having = $having;
//		$this->group->injectDB($this->db);
	}

	public function setOrder(SQLOrder $order)
	{
		$this->order = $order;
//		$this->group->injectDB($this->db);
	}

	public function setLimit(SQLLimit $limit)
	{
		$this->limit = $limit;
	}

	public static function sqlSH($sql)
	{
		$res = '';
		$words = ['SELECT', 'FROM', 'WHERE', 'GROUP', 'BY', 'ORDER', 'HAVING', 'AND', 'OR', 'LIMIT', 'OFFSET', 'LEFT', 'OUTER', 'INNER', 'RIGHT', 'JOIN', 'CASE', 'WHEN', 'THEN', 'ELSE', 'END', 'AS', 'DISTINCT', 'ON', 'NATURAL'];
		$breakAfter = ['SELECT', 'BY', 'OUTER', 'ON', 'DISTINCT', 'AS', 'WHEN', 'NATURAL'];
		$sql = str_replace('(', ' ( ', $sql);
		$sql = str_replace(')', ' ) ', $sql);
		$level = 0;
		$open = false;
		$tok = strtok($sql, " \n\t");
		while ($tok !== false) {
			$tok = trim($tok);
			if ($tok == '(') {
				$level++;
				$res .= ' (' . '<br>' . str_repeat('&nbsp;', $level * 4);
			} elseif ($tok == ')') {
				if ($level > 0) {
					$level--;
				}
				$res .= '<br>' . str_repeat('&nbsp;', $level * 4) . ') ';
			} elseif ($tok && ($tok[0] == "'" || $tok[strlen($tok) - 1] == "'" || $tok === "'")) {
				$res .= ' ';
				if ($tok[0] == "'" && !$open) {
					$res .= '<font color="green">';
					$open = true;
				}
				$res .= $tok;
				if ($tok[strlen($tok) - 1] === "'" && $open) {
					$res .= '</font>';
					$open = false;
				}
			} elseif (is_numeric($tok)) {
				$res .= ' <font color="red">' . $tok . '</font>';
			} elseif (in_array(strtoupper($tok), $words)) {
				$br = strlen($res) ? '<br>' : '';
				$strange = $tok == 'SELECT' ? '' : ' ';
				$res .= (!in_array($tok, $breakAfter)
					? ' ' . $br . str_repeat('&nbsp;', $level * 4)
					: $strange);
				$res .= '<font color="blue">' . strtoupper($tok) . '</font>';
			} else {
				$res .= ' ' . $tok;
			}
			//print('toc: '.$tok.' ');
			$tok = strtok(" \n\t");
		}
		$res = trim($res);
		$res = str_replace('(<br><br>)', '()', $res);
		$res = str_replace('(<br>&nbsp;&nbsp;&nbsp;&nbsp;<br>)', '()', $res);
		return new HtmlString($res);
	}

	/**
	 * @param DBInterface $db
	 * @param string $table
	 * @param array|SQLWhere $where
	 * @param string $sOrder
	 * @param string $addSelect
	 * @return SQLSelectQuery
	 */
	public static function getSelectQueryP(
		DBInterface $db,
								$table,
								$where = [],
								$sOrder = '',
								$addSelect = null
	)
	{
		$table1 = SQLBuilder::getFirstWord($table);
		if ($table == $table1) {    // NO JOIN
			$from = /*$this->db->quoteKey*/
				($table1);    // table name always quoted
			$join = null;
		} else {                    // JOIN
			$join = substr($table, strlen($table1));
			$from = $table1; // not quoted
		}


		// must be quoted for SELECT user.* ... because "user" is reserved
		$select = $addSelect ?: $db->quoteKey($table1) . '.*';

		$select = new SQLSelect($select);
		$select->injectDB($db);

		$from = new SQLFrom($from);
		$from->injectDB($db);

		if ($join) {
			$join = new SQLJoin($join);
		}

		if (is_array($where)) {
			$where = new SQLWhere($where);
		}
		$where->injectDB($db);

		$sOrder = trim($sOrder);
		$group = null;
		$limit = null;
		$order = null;
		if (str_startsWith($sOrder, 'ORDER BY')) {
			$order = new SQLOrder($sOrder);
			$order->injectDB($db);
			$group = null;
		} elseif (str_startsWith($sOrder, 'GROUP BY')) {
			$parts = trimExplode('ORDER BY', $sOrder);
			$group = new SQLGroup($parts[0]);
			$group->db = $db;
			if (ifsetor($parts[1])) {
				$order = new SQLOrder($parts[1]);
				$order->injectDB($db);
			}
		} elseif (str_startsWith($sOrder, 'LIMIT')) {
			$parts = trimExplode('LIMIT', $sOrder);
			$limit = new SQLLimit($parts[0]);
		} elseif ($sOrder) {
			debug(['sOrder' => $sOrder, 'order' => $order]);
			throw new InvalidArgumentException(__METHOD__);
		}
		//		debug(__METHOD__, $table, $where, $where->getParameters());
		$sq = new SQLSelectQuery($select, $from, $where, $join, $group, null, $order, $limit);
		$sq->injectDB($db);
		return $sq;
	}

	public function injectDB(DBInterface $db)
	{
		//debug(__METHOD__, gettype2($db));
		$this->db = $db;
		$this->from->injectDB($this->db);
		if ($this->where) {
			$this->where->injectDB($this->db);
		}
	}

	public function getDistance($lat, $lon, $latitude = 'latitude', $longitude = 'longitude')
	{
		if ($this->db->isSQLite()) {
			$this->db->getConnection()->sqliteCreateFunction('sqrt', function ($a) {
				return sqrt($a);
			}, 1);
			return "sqrt(($latitude - ($lat))*($latitude - ($lat)) + ($longitude - ($lon))*($longitude - ($lon))) AS distance";
		}

		return "( 6371 * acos( cos( radians($lat) ) * cos( radians( $latitude ) )
			* cos( radians( $longitude ) - radians($lon) ) + sin( radians($lat) ) * sin(radians($latitude)) ) ) AS distance";
	}

	public function __toString()
	{
		try {
			return $this->getQuery();
		} catch (Exception $e) {
			echo '<strong>', $e->getMessage(), '</strong>', BR;
			//echo '<strong>', $e->getPrevious()->getMessage(), '</strong>', BR;
			pre_print_r($e->getTraceAsString());
			return '<strong>' . $e->getMessage() . '</strong>' . BR;
		}
	}

	/**
	 * @return string
	 */
	public function getQuery()
	{
		$query = trim("SELECT
{$this->select}
FROM {$this->from}
{$this->join}
{$this->where}
{$this->group}
{$this->having}
{$this->order}
{$this->limit}");
		// http://stackoverflow.com/a/709684
		$query = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $query);
		//		debug($this->where, $query, $this->getParameters());
		return $query;
	}

	public function fetchAssoc()
	{
		return $this->db->fetchAssoc($this->perform());
	}

	/**
	 * A way to perform a query with parameter without making a SQL
	 */
	public function perform()
	{
		$sQuery = $this->getQuery();
		$aParams = $this->getParameters();
		//		debug(['where' => $this->where, 'sql' => $sQuery, 'params' => $aParams]);
		return $this->db->perform($sQuery, $aParams);
	}

	public function getParameters()
	{
		if ($this->where) {
			$params = $this->where->getParameters();
		} else {
			$params = [];
		}
		if ($this->from instanceof SQLSubquery) {
			$subParams = $this->from->getParameters();
			//			debug($subParams);
			$params += $subParams;
		}
		return $params;
	}

	public function fetchAll()
	{
		return $this->db->fetchAll($this->perform());
	}

	public function unsetOrder()
	{
		$this->order = null;
	}

	public function getFrom()
	{
		return $this->from;
	}

	public function setFrom(SQLFrom $from)
	{
		$from->db = $this->db;
		$this->from = $from;
	}

	public function getWhere()
	{
		return $this->where;
	}

	public function setWhere(SQLWhere $where)
	{
		$this->where = $where;
		$this->where->injectDB($this->db);
	}

	public function join($table, $on)
	{
		$this->join = new SQLJoin('LEFT OUTER JOIN ' . $table . ' ON (' . $on . ')');
		return $this;
	}

	public function select($what)
	{
		$this->select = new SQLSelect($what);
		return $this;
	}

	public function getSelect()
	{
		return $this->select;
	}

	public function setSelect(SQLSelect $select)
	{
		$this->select = $select;
	}
	
}
