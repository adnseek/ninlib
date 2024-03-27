<?php

/**
 * This class is a replacement for fetchAll - foreach combination and should be used to reduce the memory
 * requirements of the script. It's ment to mimic Iterator classes in PHP5, but doesn't inherit the interface
 * completely. (wrong!?)
 *
 * The correct order is
 * rewind()
 *
 * [
 *    next()
 *    valid()
 *    current()
 * ]
 */
class DatabaseResultIterator implements Iterator, Countable
{

	/**
	 * If defined it will influence the key() method return value
	 * @var string
	 */
	public $defaultKey;

	/**
	 * Query result
	 * @var resource
	 */
	public $dbResultResource;

	/**
	 * Must be false to indicate no results
	 * @var array
	 */
	public $row = false;

	/**
	 * Amount. Must be NULL for the first time.
	 * @var int
	 */
	protected $rows = null;

	/**
	 * Will return the value of the current row corresponding to $this->defaultKey
	 * or number 0, 1, 2, 3, ... otherwise
	 * @var int
	 */
	public $key = 0;

	/**
	 * @var DBInterface
	 */
	protected $db;

	public $query;

	public $debug = false;

	/**
	 * DatabaseResultIterator constructor.
	 * @param DBInterface $db
	 * @param string $defaultKey = 'id', or 'uid'
	 */
	public function __construct(DBInterface $db, $defaultKey = null)
	{
		$this->db = $db;
		$this->defaultKey = $defaultKey;
	}

	public function setResult($res)
	{
		$this->dbResultResource = $res;
		// performance increase
//		$this->rows = $this->count();
	}

	public function perform($query)
	{
		$this->log(__METHOD__);
		$this->query = $query;
		$params = [];
		if ($query instanceof SQLSelectQuery) {
			$params = $query->getParameters();
			//debug($query, $params);
		}
		$this->log('call perform() on ' . get_class($this->db));
		$this->dbResultResource = $this->db->perform($query, $params);
		$this->log(__METHOD__, [
			'dbResultResource' => $this->dbResultResource
		]);
		$this->rows = $this->count();
		//$this->rewind();
	}

	public function count(): int
	{
		$this->log(__METHOD__);
		if (!is_null($this->rows)) {
			return $this->rows;
		}
		$numRows = $this->db->numRows($this->dbResultResource);
		$this->log(__METHOD__, ['numRows' => $numRows]);
		return $numRows;
	}

	public function rewind():void
	{
		$this->log(__METHOD__);
		if ($this->count()) {
			$this->key = 0;
			$this->db->dataSeek($this->dbResultResource, 0);
			$this->next();
		}
	}

	public function current(): mixed
	{
		$this->log(__METHOD__);
		return $this->row;
	}

	public function key(): mixed
	{
		return $this->key;
	}

	public function next(): void
	{
		$this->log(__METHOD__);
		$this->row = $this->retrieveRow();
		if ($this->row) {
			if ($this->defaultKey) {
				$this->key = ifsetor($this->row[$this->defaultKey]);
			} else {
				$this->key++;
			}
		}
		//debug($this->key, $this->row);
	}

	public function retrieveRow()
	{
		$this->log(__METHOD__);
		$row = $this->db->fetchAssoc($this->dbResultResource);
//		debug(__METHOD__, $row);
		return $row;
	}

	public function valid(): bool
	{
		$this->log(__METHOD__);
		return $this->row !== null && $this->row !== false;
	}

	/**
	 * Should not be used - against the purpose, but nice for debugging
	 * @return array
	 */
	public function fetchAll()
	{
		$this->log(__METHOD__);
		$data = [];
		foreach ($this as $row) {
			$data[] = $row;
		}
		return $data;
	}

	public function __destruct()
	{
		$this->log(__METHOD__);
		$this->db->free($this->dbResultResource);
	}

	public function skip($rows)
	{
		$this->log(__METHOD__, $rows);
		while ($rows) {
			$this->next();
			$rows--;
		}
		$this->key += $rows;
		return $this;
	}

	public function log($method, $data = null)
	{
		if ($this->debug) {
			debug($method, $data);
		}
	}

}
