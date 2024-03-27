<?php

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 22.01.2016
 * Time: 17:27
 */
class HTMLTagTest extends PHPUnit\Framework\TestCase
{

	public function test_parse_simple()
	{
		$str = '<a>';
		$tag = HTMLTag::parse($str);
		$this->assertEquals('a', $tag->tag);
	}

	public function test_parse_simple_space()
	{
		$str = ' <a > ';
		$tag = HTMLTag::parse($str);
		$this->assertEquals('a', $tag->tag);
	}

	public function test_parse_attrib()
	{
		$str = '<a href="http://asd.com/">';
		$tag = HTMLTag::parse($str);
		$this->assertEquals('a', $tag->tag);
		$this->assertEquals('http://asd.com/', $tag->attr['href']);
	}

	public function test_parse_inner()
	{
		$str = '<a href="http://asd.com/">Text</a>';
		$tag = HTMLTag::parse($str);
		$this->assertEquals('a', $tag->tag);
		$this->assertEquals('http://asd.com/', $tag->attr['href']);
		$this->assertEquals('Text', $tag->content);
	}

	public function test_parse_recursive()
	{
		$str = '<a href="http://asd.com/"><b>Text</b></a>';
		$tag = HTMLTag::parse($str, true);
		$this->assertEquals('a', $tag->tag);
		$this->assertEquals('http://asd.com/', $tag->attr['href']);
		$this->assertIsArray($tag->content);
		//pre_print_r($tag);
	}

	public function test_parse_recursive_back()
	{
		$str = "<a href=\"http://asd.com/\"><b>Text</b>\n</a>\n";
		$tag = HTMLTag::parse($str, true);
		$this->assertEquals('a', $tag->tag);
		$this->assertEquals('http://asd.com/', $tag->attr['href']);
		$this->assertIsArray($tag->content);
		$back = $tag->__toString();
		//pre_print_r($str, $back);
		$this->assertEquals($str, $back);
	}

	public function test_pre()
	{
		$title = HTMLTag::pre(json_encode(
			'something', JSON_PRETTY_PRINT
		), ['style' => [
			'white-space' => 'pre-wrap'
		]]);
		$this->assertEquals('<pre style="white-space: pre-wrap">&quot;something&quot;</pre>' . "\n", $title . '');
	}

}
