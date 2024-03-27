<?php

class SQLOrder extends SQLWherePart
{

	protected $db;

	protected $parts = [];

	public function __construct($order = [])
	{
		if (is_array($order)) {
			$this->parts = $order;
		} elseif ($order) {
			$this->parts = trimExplode(' ', str_replace('ORDER BY', '', $order));
		}
	}

	public function __toString()
	{
		if ($this->parts) {
			return 'ORDER BY ' . implode(' ', $this->parts);
		} else {
			return '';
		}
	}

	public function getField()
	{
		return first($this->parts);
	}

}
