<?php
/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 10.12.2018
 * Time: 14:08
 */

class JustIdAndName extends POPOBase
{

	/**
	 * @var int
	 */
	public $id;

	public $name;

	/**
	 * @var DateTime
	 */
	public $date;

}

class POPOBaseTest extends PHPUnit\Framework\TestCase
{

	public function test__construct()
	{
		self::markTestSkipped('PG dependent');
		$struct = [
			'id' => '1',
			'name' => '@slawa',
			'date' => '2018-12-10',
		];
		$json = (object)$struct;

		$p = new JustIdAndName($json);
		$this->assertNotEmpty($p->id);
		$this->assertTrue(is_int($p->id));
		$this->assertTrue(is_string($p->name));
		$this->assertInstanceOf(DateTime::class, $p->date);
	}
}
