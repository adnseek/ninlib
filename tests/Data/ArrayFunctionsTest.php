<?php

if (!defined('DEVELOPMENT')) {
	define('DEVELOPMENT', true);
}

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 23.11.2016
 * Time: 11:45
 */
class ArrayFunctionsTest extends PHPUnit\Framework\TestCase
{

	public function test_unique_multidim_array_thru()
	{
		$fixture = [
			'a' => 'b',
			'c' => 'b', // del
			'e' => [
				'a' => 'b',
				'b' => 'b',  // del
			],
		];
		$unique = unique_multidim_array_thru($fixture);
//		debug($unique);
		$this->assertEquals([
			'a' => 'b',
			'e' => [
				'a' => 'b',
			]
		], $unique);
	}

	function test_array_find()
	{
		$data = [
			'asd',
			'qwe',
			'123',
		];
		$this->assertEquals('qwe', array_find(static function ($line) {
			return str_startsWith($line, 'q');
		}, $data));
	}

}
