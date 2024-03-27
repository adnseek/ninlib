<?php
/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 20.11.2018
 * Time: 16:53
 */


class SQLWhereEqualTest extends PHPUnit\Framework\TestCase
{

	/** @var DBInterface */
	protected $db;

	public function setUp(): void
	{
		parent::setUp();
		self::markTestSkipped('PG dependent');
		$this->db = Config::getInstance()->getDB();
	}

	public function testGetWhereItem()
	{
		$swe = new SQLWhereEqual('field', 15);
		$swe->injectDB($this->db);
		$sql = $swe->__toString();
		$this->assertEquals('"field" = \'15\'', $sql);
	}

	public function testGetWhereItemAsIs()
	{
		$swe = new SQLWhereEqual('field', new AsIs('15'));
		$swe->injectDB($this->db);
		$sql = $swe->__toString();
		$this->assertEquals('"field" = 15', $sql);
	}

}
