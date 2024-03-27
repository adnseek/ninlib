<?php
/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 06.06.2017
 * Time: 11:27
 */

namespace nadlib\Base;

use LazyLoader;
use PHPUnit\Framework\TestCase;
use stdClass;

class LazyLoaderTest extends TestCase
{

	public function test_ll_same_value()
	{
		$l1 = new LazyLoader('123');
		$v1 = $l1();
		$v2 = $l1();
		$this->assertEquals('123', $v1);
		$this->assertEquals('123', $v2);
	}

	public function test_ll_same_object()
	{
		$l1 = new LazyLoader(new stdClass());
		$v1 = $l1();
		$v2 = $l1();
		$this->assertEquals(spl_object_hash($v1), spl_object_hash($v2));
	}

	public function test_ll_time_once()
	{
		$l1 = new LazyLoader(function () {
			sleep(1);
		});
		$start = microtime(true);
		$v1 = $l1();
		$v2 = $l1();
		$duration = microtime(true) - $start;
//		echo __METHOD__, ': ', $duration, PHP_EOL;
		$this->assertLessThan(2, $duration);
	}

}
