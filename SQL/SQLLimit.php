<?php

class SQLLimit
{

	public $limit;

	public $offset;

	public function __construct($limit, $offset = 0)
	{
		$this->limit = $limit;
		$this->offset = $offset;
	}

	public function __toString()
	{
		$content = 'LIMIT ' . $this->limit;
		if ($this->offset) {
			$content .= ' OFFSET ' . $this->offset;
		}
		return $content;
	}

}
