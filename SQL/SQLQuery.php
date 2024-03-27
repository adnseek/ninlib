<?php

use PHPSQLParser\PHPSQLCreator;
use PHPSQLParser\PHPSQLParser;

class SQLQuery extends PHPSQLParser
{

	public $parsed;

	public function __construct($sql = false, $calcPositions = false)
	{
		if ($sql instanceof self) {
			$this->parsed = $sql->parsed;
		} else {
			parent::__construct($sql, $calcPositions);
		}
	}

	public function __toString()
	{
		return $this->getQuery();
	}

	public function getQuery(): string
	{
		$psc = new PHPSQLCreator($this->parsed);
		$query = $psc->created . '';
		return str_replace([
			'SELECT',
			'FROM',
			'WHERE',
			'GROUP',
			'HAVING',
			'ORDER',
			'LIMIT',
		], [
			"SELECT",
			"\nFROM",
			"\nWHERE",
			"\nGROUP",
			"\nHAVING",
			"\nORDER",
			"\nLIMIT",
		], $query);
	}

	public function appendCalcRows()
	{
		//debug($sql->parsed['SELECT']);
		array_unshift($this->parsed['SELECT'], [
			'expr_type' => 'reserved',
			'base_expr' => 'SQL_CALC_FOUND_ROWS',
			'delim' => ' ',
		]);
		//debug($sql->parsed);
		if ($this->parsed['ORDER'] && $this->parsed['ORDER'][0]['base_expr'] !== 'FIELD') {
			$this->parsed['ORDER'][0]['expr_type'] = 'colref';
		}
		//debug($sql->parsed);
	}

}
