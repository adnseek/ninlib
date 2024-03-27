<?php

class SQLJoin
{

	protected $parts = [];

	public function __construct($join = null)
	{
		if (is_array($join)) {
			$this->parts = $join;
		} else {
			$this->parts[] = $join;
		}
	}

	public function add($join)
	{
		if (!in_array($join, $this->parts)) {
			$this->parts[] = $join;
		}
	}

	public function __toString()
	{
		if ($this->parts) {
			return implode("\n", $this->parts);
		}
		return '';
	}

}
