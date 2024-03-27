<?php

class DateTest extends PHPUnit\Framework\TestCase
{

	public function test_Date()
	{
		$d = new Date(1306879200);
		$this->assertEquals($d, '01.06.2011');
	}

	public function test_Date_preserveTimestamp()
	{
		$d = new Date(1306879200);
		$this->assertEquals($d->getTimestamp(), 1306879200);
	}

	/* // Asserting!
	function test_Date_preserveTimestamp_string() {
		try {
			$d = new Date('1306879200');
		} catch (Exception $e) {

		}
		return $this->assertEqual($d->getTimestamp(), 1306879200);
	}*/

	public function test_NewDateNewDate()
	{
		$d = new Date('2011-11-11');
		$d2 = new Date($d);
		$this->assertEquals($d, $d2);
	}

}
