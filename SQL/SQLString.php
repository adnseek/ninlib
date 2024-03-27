<?php

class SQLString extends SQLWherePart
{

	protected $value;

	public function __construct($value)
	{
		parent::__construct();
		$this->value = $value;
	}

	public function __toString()
	{
		return $this->field . " = '" . $this->db->escape($this->value) . "'";
	}

}
