<?php

class DSNBuilderMySQL extends DSNBuilder
{

	public $host;
	public $user;
	public $pass;
	public $db;
	public $port;

	public function __construct($host, $user, $pass, $db, $port = 3306)
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
			'DATABASE' => $this->db,
			'host' => $this->host,
			'SYSTEM' => $this->host,
			'dbname' => $this->db,
			'HOSTNAME' => $this->host,
			'PORT' => $this->port,
			'PROTOCOL' => 'TCPIP',
		];
		return 'mysql:' . $this->getDSN($aDSN);
	}

}
