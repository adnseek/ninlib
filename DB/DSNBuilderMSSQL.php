<?php

class DSNBuilderMSSQL extends DSNBuilder
{

	public $host;
	public $user;
	public $pass;
	public $db;
	public $port;
	public $driver;

	public function __construct($host, $user, $pass, $db, $port = 3306)
	{
		$this->host = $host;
		$this->user = $user;
		$this->pass = $pass;
		$this->db = $db;
		$this->port = $port;
	}

	public function setDriver($driver)
	{
		$this->driver = $driver;
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
			'DRIVER' => '{' . $this->driver . '}',
		];
		return 'mssql:' . $this->getDSN($aDSN);
	}

}
