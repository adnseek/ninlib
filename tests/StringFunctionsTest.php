<?php

class StringFunctionsTest extends PHPUnit\Framework\TestCase
{

	public function test_cap()
	{
		$this->assertEquals('asd/', cap('asd'));
	}

	public function test_contains()
	{
		$this->assertTrue(contains('abc', 'b'));
	}

	public function test_containsAny()
	{
		$this->assertTrue(containsAny('bc', ['a', 'bc', 'cde']));
	}

	public function test_path_plus()
	{
		$this->assertEquals('/asd/qwe', path_plus('/asd', 'qwe'));
		$this->assertEquals('/asd/qwe', path_plus('/asd/', 'qwe'));
		$this->assertEquals('/asd/qwe', path_plus('/asd/', '/qwe'));
		$this->assertEquals('/asd/qwe', path_plus('/asd/', '/qwe/'));
		$this->assertEquals('asd/qwe', path_plus('asd/', '/qwe/'));
	}

	public function test_path_plus_twice()
	{
		$this->assertEquals('/asd/qwe/zxc', path_plus('/asd', 'qwe', 'zxc'));
	}

	public function test_str_contains()
	{
		$this->assertFalse(str_contains('q', 'abc'));
	}

	public function test_str_endsWith()
	{
		$this->assertTrue(str_endsWith('abc', 'bc'));
		$this->assertFalse(str_endsWith('abc', 'qwe'));
	}

	public function test_str_icontains()
	{
		$this->assertTrue(str_icontains('ABC', 'bc'));
	}

	public function test_str_replace_once()
	{
		$this->assertEquals('qwe**', str_replace_once('*', 'e', 'qw***'));
	}

	public function test_str_startsWith()
	{
		$this->assertTrue(str_startsWith('abc', 'a'));
		$this->assertFalse(str_startsWith('abc', 'q'));
	}

	public function test_tab2nbsp()
	{
		$this->assertEquals("a&nbsp;&nbsp;b", tab2nbsp("a\tb", 2));
	}

	public function test_tabify()
	{
		$this->assertEquals("a\tb", tabify(['a', 'b']));
	}

	public function test_toCamelCase()
	{
		$this->assertEquals('Tocamelcase', toCamelCase('tocamelcase'));
	}

	public function test_toDatabaseKey()
	{
		$this->assertEquals('this_-should__be_key', toDatabaseKey('this-should_beKey'));
	}

	public function test_trimExplode()
	{
		$path = '/asd/qwe///';
		$parts = trimExplode('/', $path);
		$this->assertEquals(['asd', 'qwe'], $parts);
	}

	public function test_unquote()
	{
		$this->assertEquals('asd', unquote('"asd"'));
	}

}
