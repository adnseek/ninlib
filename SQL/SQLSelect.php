<?php

class SQLSelect
{

	/**
	 * @var DBInterface
	 */
	public $db;

	public $parts = [];

	/**
	 * SQLSelect constructor.
	 *
	 * @param $parts array|string
	 */
	public function __construct($parts)
	{
		if (is_array($parts)) {
			$this->parts = $parts;
		} elseif ($parts) {
			$this->parts[] = $parts;
		} else {
			$this->parts[] = '*';
		}
	}

	public function injectDB(DBInterface $db)
	{
		$this->db = $db;
	}

	public function __toString()
	{
		return implode(', ', $this->parts);
	}

	public function contains($string)
	{
		foreach ($this->parts as $p) {
			if (str_contains($p . '', $string)) {
				return true;
			}
		}
		return false;
	}

}
