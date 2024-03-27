<?php

class DSNBuilder
{

	public static function make($scheme, $host, $user = null, $pass = null, $db = null, $port = null)
	{
		$classMap = [
			'mysql' => DSNBuilderMySQL::class,
			'pgsql' => DSNBuilderPostgreSQL::class,
			'sqlite' => DSNBuilderSQLite::class,
			'mssql' => DSNBuilderMSSQL::class,
		];
		$dsnClass = $classMap[$scheme];
		$builder = new $dsnClass($host, $user, $pass, $db, $port);
		return $builder;
	}

	public function getDSN(array $params)
	{
		$url = http_build_query($params, null, ';', PHP_QUERY_RFC3986);
		$url = str_replace('%20', ' ', $url);    // back convert
		$url = urldecode($url);
		return $url;
	}

}
