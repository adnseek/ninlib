<?php

/**
 * Class SQLBetween
 */
class SQLBetween extends SQLWherePart
{

	/**
	 * @var mixed
	 */
	public $start;

	/**
	 * @var mixed
	 */
	public $end;

	/**
	 * @var DBInterface|DBLayerBase
	 */
	protected $db;

	public function __construct($start, $end)
	{
		parent::__construct();
		$this->start = $start;
		$this->end = $end;
	}

	public function toString($field)
	{
		return /*$this->db->quoteKey*/ ($field) . ' BETWEEN ' . $this->db->quoteSQL($this->start) . ' AND ' . $this->db->quoteSQL($this->end);
	}

	public function __toString()
	{
		return $this->toString($this->field);
	}

	public function debug()
	{
		return $this->__toString();
	}

}
