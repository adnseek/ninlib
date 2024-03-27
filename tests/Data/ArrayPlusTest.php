<?php

class ArrayPlusTest extends IteratorArrayAccessTest
{

	/**
	 * @var ArrayPlus $ai
	 */
	protected $ai;

	public function setUp(): void
	{
		$this->ai = new ArrayPlus([
			0 => 'a',
			1 => 'b',
			'slawa' => 'test',
		]);
	}

	public function test_typoscript()
	{
		$a = [
			'a' => 'b',
			'c' => [
				'd' => 'e',
				'f' => [
					'g' => 'h',
				],
			],
		];
		$b = ArrayPlus::create($a)->typoscript();
		//debug($b);
		$this->assertEquals([
			'a' => 'b',
			'c.d' => 'e',
			'c.f.g' => 'h',
		], $b);
	}

	public function test_unset()
	{
		unset($this->ai[1]);
		$this->assertEquals([
			0 => 'a',
			'slawa' => 'test'
		], $this->ai->getData());
	}

	public function test_addColumn()
	{
		$this->ai->makeTable('col1');
		$this->ai->addColumn('nr', function ($row, $i) {
			return $i;
		});
		$this->assertEquals([
			[
				'col1' => 'a',
				'nr' => 0,
			],
			[
				'col1' => 'b',
				'nr' => 1,
			],
			'slawa' => [
				'col1' => 'test',
				'nr' => 'slawa',
			]
		], (array)$this->ai);
	}

	public function test_remap()
	{
		$a = ArrayPlus::create([
			'a' => 'b',
			'c' => 'd',
		]);
		$remap = $a->remap([
			'A' => 'a',
		]);
//		debug($remap);
		$this->assertEquals([
			'A' => 'b',
		], $remap->getData());
	}

	public function test_mapBoth()
	{
		$a = ArrayPlus::create([
			'a' => 'b',
		]);
		$b = $a->mapBoth(function ($key, $el) {
			return $key;
		});
		$this->assertEquals([
			'a',
		], $b->getKeys()->getData());
	}

	public function test_insertBefore()
	{
		$a = ArrayPlus::create(['asd', 'split' => 'a', 'after']);
		$a->insertBefore('split', 'someshit');
		$this->assertEquals(['asd', 'someshit', 'split' => 'a', 'after'], $a->getData());
	}

	public function test_without()
	{
		$a = ArrayPlus::create(['asd' => 1, 'qwe' => 2]);
		$b = $a->without(['asd']);
		$this->assertEquals(['qwe' => 2], $b->getArrayCopy());
	}

	public function test_any()
	{
		$fixture01 = ArrayPlus::create([0, 1]);
		$this->assertTrue($fixture01->any(function ($x) {
			return $x;
		}));
		$fixture00 = ArrayPlus::create([0, 0]);
		$this->assertFalse($fixture00->any(function ($x) {
			return $x;
		}));
		$fixture11 = ArrayPlus::create([1, 1]);
		$this->assertTrue($fixture11->any(function ($x) {
			return $x;
		}));
	}

	public function test_all()
	{
		$fixture01 = ArrayPlus::create([0, 1]);
		$this->assertFalse($fixture01->all(function ($x) {
			return $x;
		}));
		$fixture00 = ArrayPlus::create([0, 0]);
		$this->assertFalse($fixture00->all(function ($x) {
			return $x;
		}));
		$fixture11 = ArrayPlus::create([1, 1]);
		$this->assertTrue($fixture11->all(function ($x) {
			return $x;
		}));
	}

	public function test_none()
	{
		$fixture01 = ArrayPlus::create([0, 1]);
		$this->assertFalse($fixture01->none(function ($x) {
			return $x;
		}));
		$fixture00 = ArrayPlus::create([0, 0]);
		$this->assertTrue($fixture00->none(function ($x) {
			return $x;
		}));
		$fixture11 = ArrayPlus::create([1, 1]);
		$this->assertFalse($fixture11->none(function ($x) {
			return $x;
		}));
	}

}
