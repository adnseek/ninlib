<?php

class DurationTest extends PHPUnit\Framework\TestCase
{

	function test_Duration_fromHuman()
	{
		$d = Duration::fromHuman('1s');
		$this->assertEquals($d . '', '1 second');
	}

	function test_Duration_fromHuman2()
	{
		$d = Duration::fromHuman('70m 60s');
		$this->assertEquals($d . '', '1 hour, 11 minutes');
	}

}
