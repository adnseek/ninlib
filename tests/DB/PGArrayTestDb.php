<?php

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 16.11.2016
 * Time: 15:30
 */
class PGArrayTestDb extends PHPUnit\Framework\TestCase
{

	/**
	 * @var DBInterface
	 */
	public $db;

	public function setUp(): void
	{
		$this->db = Config::getInstance()->getDB();
	}

	public function test_lineBreak()
	{
		$pga = new PGArray($this->db);
		$fixture = [
			'b',
			"d
e",
		];
		var_export($fixture);
		echo PHP_EOL;

		$string = $pga->setPGArray($fixture);
		var_export($string);
		echo PHP_EOL;
		echo 'fixture: ', $this->serialize($fixture), PHP_EOL;
		echo 'encode: ', $this->serialize($string), PHP_EOL;
		$this->assertEquals('{"b","d
e"}', $string);

		$decode = $pga->getPGArray($string);
		var_export($decode);
		echo PHP_EOL;
		echo 'fixture: ', $this->serialize($fixture), PHP_EOL;
		echo 'decode: ', $this->serialize($decode), PHP_EOL;
		$this->assertEquals($fixture, $decode);
	}

	public function serialize($var)
	{
		$serial = serialize($var);
		$serial = str_replace("\n", '{0x0A}', $serial);
		$serial = str_replace("\r", '{0x0D}', $serial);
		return $serial;
	}

	public function test_PGArray_toString()
	{
		$fixture = [
			1,
			2,
			"slawa",
			"multi
line"
		];
		$pga = new PGArray($this->db, $fixture);
		$insert = $this->db->getInsertQuery('asd', [
			'arrayField' => $pga,
		]);
		debug($insert);
		$this->assertEquals("INSERT INTO \"asd\" (\"arrayField\") VALUES (ARRAY['1', '2', 'slawa', 'multi
line'])", $insert);
	}

}
