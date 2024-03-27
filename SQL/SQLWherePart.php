<?php

/**
 * Base class in order to check instanceof SQLWherePart
 */

class SQLWherePart
{

	/**
	 * @var DBInterface
	 */
	protected $db;

	protected $sql = '';

	/**
	 * @var string
	 */
	protected $field;

	public function __construct($sql = '')
	{
		$this->sql = $sql;
//		$this->db = Config::getInstance()->getDB();
	}

	/**
	 * Not used directly
	 * @return string
	 * @throws MustBeStringException
	 * @see SQLWhereEqual
	 */
	public function __toString()
	{
		if ($this->field && !is_numeric($this->field)) {
			$part1 = $this->db->quoteWhere(
				[$this->field => $this->sql]
			);
			return implode('', $part1);
		} else {
			return $this->sql . '';
		}
	}

	public function injectDB(DBInterface $db)
	{
		//debug(__METHOD__, gettype2($db));
		$this->db = $db;
	}

	public function injectField($field)
	{
		$this->field = $field;
	}

	public function debug()
	{
		return [
			'class' => get_class($this),
			'sql' => $this->sql,
		];
	}

	/**
	 * Sub-classes should return their parameters
	 * @return null
	 */
	public function getParameter()
	{
		return null;
	}

	public function perform()
	{
		return $this->db->perform($this->__toString());
	}

	public function getField()
	{
		return $this->field;
	}

}
