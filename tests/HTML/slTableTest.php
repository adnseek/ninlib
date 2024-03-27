<?php

class slTableTest extends PHPUnit\Framework\TestCase
{

	public function test_construct()
	{
		$s = new slTable();
		$this->assertEquals($s->more, [
			'class' => 'nospacing',
		]);
	}

	public function test_construct_with_more()
	{
		$s = new slTable([], 'class="whatever"');
		$this->assertEquals($s->more, [
			'class' => 'whatever',
		]);
	}

	public function test_construct_with_more_array()
	{
		$s = new slTable([], ['class' => "whatever"]);
		$this->assertEquals($s->more, [
			'class' => 'whatever',
		]);
	}

	public function test_construct_with_more_id()
	{
		$s = new slTable([], ['class' => "whatever", 'id' => 'qwe']);
		$this->assertEquals([
			'class' => 'whatever',
			'id' => 'qwe',
		], $s->more);
		$this->assertEquals('qwe', $s->ID);
	}

	public function test_construct_with_more_id_string()
	{
		$s = new slTable([], 'id="qwe"');
		$this->assertEquals([
			'id' => 'qwe',
		], $s->more);
		$this->assertEquals('qwe', $s->ID);
	}

	public function test_construct_with_more_id_string_more()
	{
		$s = new slTable([], 'id="qwe" cellpadding="2"');
		$this->assertEquals([
			'id' => 'qwe',
			'cellpadding' => 2,
		], $s->more);
		$this->assertEquals('qwe', $s->ID);
	}

	public function test_detectSortBy()
	{
		$s = new slTable([
			['a' => 2, 2, 3],
			['a' => 4, 5, 6],
			['a' => 1, 5, 6],
		]);
		$request = new Request();
		$request->setArray(['slTable' => [
			'sortBy' => 'a',
		]]);
		$s->setRequest($request);
		$s->detectSortBy();
		$this->assertEquals('a', $s->sortBy);
	}

	public function test_detectSortBy_no_data()
	{
		$s = new slTable([]);
		$request = new Request();
		$request->clear();
		$s->setRequest($request);
		$s->detectSortBy();
		$this->assertEquals([], $s->thes);
		$this->assertEquals(null, $s->sortBy);
	}

	public function test_detectSortBy_no_request()
	{
		$s = new slTable([
			['a' => 1],
		]);
		$request = new Request();
		$request->clear();
		$s->setRequest($request);
		$s->sortable = true;    // required for detectSortBy()
		$s->detectSortBy();
		$this->assertEquals([
			'a' => [
				'name' => 'a',
			],
		], $s->thes);
		$this->assertEquals('a', $s->sortBy);
	}

	public function test_detectSortBy_no_request_no_sort()
	{
		$s = new slTable([
			['a' => 1],
		]);
		$request = new Request();
		$request->clear();
		$s->setRequest($request);
		$s->sortable = false;    // required for detectSortBy()
		$s->detectSortBy();
		$this->assertEquals([], $s->thes);
		$this->assertEquals(null, $s->sortBy);
	}

	public function test_has_header()
	{
		$s = new slTable([
			['a' => 1],
		]);
//		$s->generateThes();	//  fill $s->thes
//		$s->generateThead();
//		llog('thes', $s->thes);
//		llog('gen', $s->generation);

		$html = $s->getContent();
//		echo(tidy_repair_string($html, ['indent' => true]));
		$this->assertStringContainsString('<th>a</th>', $html);
	}

}
