<?php


class DSNBuilderTest extends PHPUnit\Framework\TestCase
{

	public function testMake()
	{
		$builder = DSNBuilder::make('sqlite', null, null, null, '/asd/qwe.sqlite');
		$dsn = $builder->__toString();
//		debug($dsn);
		$this->assertEquals('sqlite:/asd/qwe.sqlite', $dsn);
	}
}
