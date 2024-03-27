<?php

class DatabaseException extends Exception
{

	public $query;

	public function setQuery($q)
	{
		$this->query = $q;
	}

	public function getQuery()
	{
		return $this->query;
	}

}
