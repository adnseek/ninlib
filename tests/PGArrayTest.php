<?php

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 15.03.2017
 * Time: 17:59
 */
class PGArrayTest extends PHPUnit\Framework\TestCase
{

	/**
	 * @var DBInterface
	 */
	protected $db;

	/**
	 * @var PGArray
	 */
	protected $sut;

	public function setUp(): void
	{
		self::markTestSkipped('PG dependent');
		$config = Config::getInstance();
		$this->db = $config->getDB();
		if (!$this->db instanceof DBLayer) {
			$this->markTestSkipped('Only for PGSQL');
		}
		$pga = new PGArray($this->db);
		$this->sut = $pga;
	}

	public function test_setPGArray_simple()
	{
		$str = $this->sut->setPGArray([1, "a", '/"\\']);
//		echo $str, BR;
		if (!($str instanceof AsIs)) {
			$str = "'" . $str . "'";
		}
		$row = $this->db->fetchAssoc("select " . $str . "::varchar[] as array");
//		debug($row['array']);
		$this->assertEquals('{1,a,"/\\"\\\\"}', $row['array']);
	}

	public function test_lineBreak()
	{
		$pga = new PGArray($this->db);
		$fixture = [
			'b',
			"d
e",
		];
//		var_export($fixture);
//		echo PHP_EOL;

		$string = $pga->setPGArray($fixture);
//		var_export($string . '');
//		echo PHP_EOL;
//		echo 'fixture: ', $this->serialize($fixture), PHP_EOL;
//		echo 'encode: ', $this->serialize($string.''), PHP_EOL;
		$this->assertEquals("ARRAY['b','d
e']", $string . '');
	}

	public function test_lineBreak_decode()
	{
		$pga = new PGArray($this->db);
		$decode = $pga->getPGArray('{"b","d
e"}');
//		var_export($decode);
//		echo PHP_EOL;
//		echo 'fixture: ', $this->serialize($fixture), PHP_EOL;
//		echo 'decode: ', $this->serialize($decode), PHP_EOL;
		$fixture = [
			'b',
			"d
e",
		];
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
//		debug($insert);
		$this->assertEquals("INSERT INTO \"asd\" (\"arrayField\") VALUES (ARRAY['1', '2', 'slawa', 'multi
line'])", $insert);
	}

	public function test_pg_array_parse()
	{
		$pga = new PGArray($this->db);
		$v1 = $pga->pg_array_parse('{{1,2},{3,4},{5}}');
		$this->assertEquals([[1, 2], [3, 4], [5]], $v1);
		$v2 = $pga->pg_array_parse('{dfasdf,"qw,,e{q\"we",\'qrer\'}');
		$this->assertEquals([
			0 => 'dfasdf',
			1 => 'qw,,e{q"we',
			2 => 'qrer',
		], $v2);
		$this->assertEquals(['', ''], $pga->pg_array_parse('{,}'));
		$this->assertEquals([], $pga->pg_array_parse('{}'));
		$this->assertEquals(null, $pga->pg_array_parse('null'));
		$this->assertEquals(null, $pga->pg_array_parse(''));
	}

	public function test_upload_request()
	{
		$pga = new PGArray($this->db);
		$res = $pga->getPGArray(file_get_contents(__DIR__ . '/upload_request.json'));
		$this->assertEquals([
			'ORSVersion:1976781',
			"LotcheckUploadRequestModel:{\"action\":\"sendRequest\",\"banner\":\"\",\"server\":\"UJI SILVER BULLET\",\"lotcheck_status\":\"Upload to test server\",\"template_id\":\"4\",\"temp_email_to\":\"epes@nintendo.de,sqc_DataUpload@hamster.nintendo.co.jp,ml_lotcheck_report_os@hamster.nintendo.co.jp,Hiroyuki.Okazaki@nintendo.de\",\"temp_email_cc\":\"howitta@nal.nintendo.com.au,Richard.Sheridan@nintendo.de,Hiroyuki.Uesugi@nintendo.de,eShop_NOE@nintendo.de,lotcheck@nintendo.de,ml-ncl-gsl-oem-lotcheck@nintendo.co.jp,ml-ncl-gsl-oem-order@nintendo.co.jp\",\"temp_email_bc\":\"\",\"temp_email_subject\":\"UPLOAD *Before Lotcheck* - 3DSWare - CN_BBEP_00.00.cia\",\"temp_email_message\":\"Dear all,\\r\\nplease be advised that the following files will be posted to our \\r\\ndirectory TESTSERVER on UJIs FTP-server.\\r\\n\\r\\nAdditional files: CN_BBEP00.zip\\n\\n\\nSystem: CTR\\/KTR\\nTitle: Pinball Breaker 2\\nFilename: CN_BBEP_00.00.cia\\nCRC: 941656BE\\n\\nSTATUS: Upload to test server\",\"queue\":\"Lotcheck\",\"queue_text\":\"Lotcheck\",\"btnSubmit\":\"Send Upload Request\",\"d\":null,\"attachments\":[\"CN_BBEP00.zip\"]}",
		], $res);
		foreach ($res as $part) {
			list($class, $params) = trimExplode(':', $part, 2);
			if ($params[0] == '{') {
				$params = json_decode($params);
				$this->assertEquals('sendRequest', $params->action);
			}
		}
	}

}

/*
SHOW standard_conforming_strings;
-- set standard_conforming_strings = 'off';
-- SHOW standard_conforming_strings;
select '{"AppBundle\Model\\\Version\\RisVersion:1912477"}'::varchar[] as array;
select ('{"AppBundle\Model\\\Version\\RisVersion:1912477"}'::varchar[])[1] as array;
select ARRAY['AppBundle\Model\Version\RisVersion:1912477'] as array;
select (ARRAY['AppBundle\Model\Version\RisVersion:1912477'])[1] as el3;
 */
