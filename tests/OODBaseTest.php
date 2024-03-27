<?php
/**
 * Created by PhpStorm.
 * User: Slawa
 * Date: 2017-03-11
 * Time: 21:50
 */

namespace nadlib\Test;

use PHPUnit\Framework\TestCase;

class OODBaseTest extends TestCase
{

	protected $sut;

	public function setUp(): void
	{
		self::markTestSkipped('PG dependent');
		$this->sut = new SpecificOODBase();
	}

	public function test_getBool()
	{
		$set = [
			0 => false,
			1 => true,
			't' => true,
			'f' => false,
			'true' => true,
			'false' => false,
			'' => false,
			'asd' => false,
			'123' => true,
		];
		foreach ($set as $source => $expected) {
//			echo $source, ' => ', $expected, BR;
			$this->sut->data[$source] = $source;
			$this->assertEquals($expected, $this->sut->getBool($source));
		}
	}

}
