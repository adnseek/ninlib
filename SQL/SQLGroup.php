<?php

class SQLGroup
{

	public $db;

	protected $parts = [];

	public function __construct($order = [])
	{
		if (is_array($order)) {
			$this->parts = $order;
		} elseif ($order) {
			$this->parts[] = str_replace('GROUP BY', '', $order);
		}
	}

	public function __toString()
	{
		if ($this->parts) {
			return 'GROUP BY ' . implode(' ', $this->parts);
		} else {
			return '';
		}
	}

}
