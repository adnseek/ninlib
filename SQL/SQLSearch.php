<?php

class SQLSearch extends SQLWherePart
{
	protected $table;
	protected $sword;
	protected $words = [];

	/**
	 * Update it from outside to search different columns
	 * @var array
	 */
	public $searchableFields = [
		'title',
	];

	/**
	 * Not used
	 * @var string
	 */
	public $queryJoins = '';

	/**
	 * Replace with ILIKE if necessary
	 * @var string
	 */
	public $likeOperator = 'LIKE';


	/**
	 * @var DBInterface
	 */
	protected $db;

	public $idField = 'id';

	public function __construct($table, $sword)
	{
		//debug(array($table, $sword));
		$this->table = $table;
		$this->sword = $sword;
		$this->words = $this->getSplitWords($this->sword);
		//debug($this->words);
		$this->db = Config::getInstance()->getDB();
		//llog(strip_tags(typ($this->db)));
	}

	public function getSplitWords($sword)
	{
		$user = Config::getInstance()->getUser();
		if ($user && $user->id) {
			$searchAppend = ifsetor($user->data['searchAppend']);
		} else {
			$searchAppend = '';
		}

		$sword = trim($sword);
		$words = explode(' ', $sword . ' ' . $searchAppend);
		$words = array_map('trim', $words);
		$words = array_filter($words);
		$words = array_unique($words);
		//$words = $this->combineSplitTags($words);
		$words = array_values($words);
		return $words;
	}

	public function __toString()
	{
		$where = $this->getWhere();
		//$query = str_replace('WHERE', $queryJoins.' WHERE', $query);
		$query = '';
		if ($where) {
			$whereString = $this->db->quoteWhere($where);
			$query .= implode(' AND ', $whereString);
		}
		return $query;
	}

	/**
	 * @return array
	 */
	public function getWhere()
	{
		$query = '';
		$where = [];
		$words = $this->words;
		//debug($words);
		foreach ($words as $word) {
			//if (!$i) {
			if (false) {
				$query .= $this->getSearchSubquery($word);
				//$query .= '( '.$this->getSearchSubquery($word).')';
				//$query .= ' AS score_'.$i;
			} else {
				$tableID = $this->table . '.' . $this->idField;
				if ($word[0] === '!') {
					$word = substr($word, 1);
					$where[] = $tableID .
						' NOT IN ( ' . $this->getSearchSubquery($word, $tableID) . ') ';
				} else {
					//$queryJoins .= ' INNER JOIN ( '.$this->getSearchSubquery($word).') AS score_'.$i.' USING (id) ';
					// join has problem: #1060 - Duplicate column name 'id' in count(*) from (select...)
					$where[] = $tableID .
						' IN ( ' . $this->getSearchSubquery($word, $tableID) . ') ';
				}
			}
		}
		return $where;
	}

	public function getSearchSubquery($word, $select = null)
	{
		$table = $this->table;
		$select = new SQLSelect($select ? $select : 'DISTINCT *');
		$from = new SQLFrom($table);
		$where = new SQLWhere([]);
		$query = new SQLSelectQuery($this->db, $select, $from, $where,
			null, null, null, new SQLOrder('id'));
		//$query->setJoin(new SQLJoin("LEFT OUTER JOIN tag ON (tag.id_score = ".$this->table.".id)"));

		//$query->where->add($this->getSearchWhere($word, $i ? 'score_'.$i : $table));
		// please put the table prefix into $this->searchableFields
		$where = $this->getSearchWhere($word);
		$where = new SQLWherePart($where);
		$query->where->add($where);
		$query->injectDB($this->db);
		return $query;
	}

	function getSearchWhere($word, $prefix = '')
	{
		if ($word[0] === '!') {
			$like = 'NOT ' . $this->likeOperator;
			$or = "\n\t\tAND";
		} else {
			$like = $this->likeOperator;
			$or = "\n\t\tOR";
		}

		$prefix = $prefix ? $prefix . '.' : '';

		$part = [];
		foreach ($this->searchableFields as $field) {
			$part[] = "{$prefix}{$field} {$like} '%$1%'";
		}
		$part = implode(' ' . $or . ' ', $part);
		$part = str_replace('$1', $this->db->escape($word), $part);
		$part = str_replace("\r\n", "\n", $part);

		// test if it's a date
		$date1 = strtotime($word);
		if (strlen($word) === 10 && $date1 > 0 && in_array('ctime', $this->searchableFields)) {
			$date2 = strtotime('+1 day', $date1);
			$date1 = date('Y-m-d', $date1);
			$date2 = date('Y-m-d', $date2);
			$part .= "
				$or $prefix.ctime BETWEEN '$date1' AND '$date2'
			";
		}

		return '(' . $part . ')'; // because of OR
	}

}
