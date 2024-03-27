<?php

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 18.01.2016
 * Time: 11:34
 */
class BytesTest extends PHPUnit\Framework\TestCase
{

	public function test_return_bytes()
	{
		$mb = ini_get('memory_limit');
		$b = new Bytes($mb);
		$b2 = Bytes::return_bytes($mb);
		$this->assertEquals($b->getBytes(), $b2);
	}

}
