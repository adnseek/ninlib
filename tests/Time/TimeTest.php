<?php

class TimeTest extends PHPUnit\Framework\TestCase
{

	public function setUp(): void
	{
		self::markTestSkipped('PG dependent');
	}

	public function test_Time_add_Duration()
	{
		$t = new Date('2011-08-01');
		$d = new Duration('1d');
		$t->addDur($d);
		$this->assertEquals($t->getSystem(), '2011-08-02', '2011-08-01 + 1d');
	}

	public function test_Time_constructor()
	{
		$t = new Time();
		//debug($t);
		$this->assertEquals($t->getTimestamp(), time());
	}

	public function test_Time_plus()
	{
		$source = '1970-01-01 12:34:56';
		$t = new Time($source);
		$t->plus(new Time('1970-01-01 00:00:01'));
		$this->assertEquals($t->getSystem(), $source);
	}

	public function test_Time_add()
	{
		$source = '1970-01-01 12:34:56';
		$t = new Time($source);
		$t->add(new Time('1970-01-01 00:00:01 GMT'));
		$this->assertEquals($t->getSystem(), '1970-01-01 12:34:57');
	}

	public function test_Time_plus_Duration()
	{
		$source = '1970-01-01 12:34:56';
		$t = new Time($source);
		$t->add(new Duration('00:00:01'));
		$this->assertEquals($t->getSystem(), '1970-01-01 12:34:57');
	}

	public function test_Time_earlier()
	{
		$source = '1970-01-01 12:34:56';
		$t = new Time($source);
		$this->assertTrue(!$t->earlier(new Time('1970-01-01 12:34:55')));
		$this->assertTrue($t->earlier(new Time('1970-01-01 12:34:57')));
		$this->assertTrue($t->earlierOrEqual(new Time($source)));
	}

	public function test_Time_addError()
	{
		$source = '1970-01-01 00:45:00';
		$sourceTS = strtotime($source);
		$this->assertEquals(date('Y-m-d H:i:s', $sourceTS) . '~' . $sourceTS, $source . '~-900');
		$target = strtotime('+ 0 years 0 months 0 days 00 hours 15 minutes 00 seconds', $sourceTS);
		$this->assertEquals(date('Y-m-d H:i:s', $target) . '~' . $target, '1970-01-01 01:00:00~0');
	}

	public function test_Time_addErrorTime()
	{
		$source = '1970-01-01 00:45:00';
		$sourceT = new Time($source);
		$target = $sourceT->plus(new Time('1970-01-01 00:14:00 GMT'), true);
		$this->assertEquals($target->getSystem(), '1970-01-01 00:59:00');
		$target = $sourceT->plus(new Time('1970-01-01 00:15:00 GMT'));
		$this->assertEquals($target->getSystem(), '1970-01-01 01:00:00');
	}

	public function test_Time_addRelative()
	{
		$source = '1970-01-01 00:45:00';
		$sourceT = new Time($source);
		$target = $sourceT->plus(new Time('00:10:00 GMT', 0));
		$this->assertEquals($target->getSystem(), '1970-01-01 00:55:00');
		$target = $sourceT->plus(new Time(10 * 60));
		$this->assertEquals($target->getSystem(), '1970-01-01 00:55:00');
		$target = new Time('00:55:00', 0);
		$this->assertEquals($target->getSystem(), '1970-01-01 00:55:00');
	}

	public function test_Time_minus()
	{
		$source = '1970-01-01 00:45:00';
		$sourceT = new Time($source);
		$target = $sourceT->minus(new Time('00:10:00 GMT', 0));
		$this->assertEquals($target->getSystem(), '1970-01-01 00:35:00');
		$target = $sourceT->minus2(new Time('00:10:00 GMT', 0));
		$this->assertEquals($target->getSystem(), '1970-01-01 00:35:00');
	}

	public function test_addTime()
	{
		$t = new Time('2016-03-08 15:05');
		$sTime = '01:12:34';
		$t->addTime($sTime);
		$this->assertEquals('2016-03-08 16:17:34', $t->getISODateTime());
	}

	public function test_getAge1()
	{
		$t = new Time('2016-03-08 15:05');
		$age = $t->getAge();
		$olderThan30 = $age->biggerThan(Duration::fromHuman('30d'));
//		debug($t, $age, $olderThan30);
		$this->assertTrue($olderThan30);
	}

	public function test_getAge2()
	{
		$t = new Time('2016-03-08 15:05');
		$age = $t->getAge();
		$olderThan30 = $age->biggerThan(Duration::fromHuman('10y'));
//		debug($t, $age, $olderThan30);
		$this->assertFalse($olderThan30);
	}

}
