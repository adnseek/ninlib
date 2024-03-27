<?php

class DSNBuilderPostgreSQL extends DSNBuilder
{

	public $host;
	public $user;
	public $pass;
	public $db;
	public $port;

	public function __construct($host, $user, $pass, $db, $port = 5432)
	{
		$this->host = $host;
		$this->user = $user;
		$this->pass = $pass;
		$this->db = $db;
		$this->port = $port;
	}

	public function __toString()
	{
		$aDSN = [
			'host' => $this->host,
			'dbname' => $this->db,
			'port' => $this->port,
		];
		return 'pgsql:' . $this->getDSN($aDSN);
	}

}
