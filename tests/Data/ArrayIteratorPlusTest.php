<?php

class ArrayIteratorPlusTest extends PHPUnit\Framework\TestCase
{

	/**
	 * @var ArrayIterator $ai
	 */
	protected $ai;

	public function setUp(): void
	{
		$this->ai = new ArrayIterator([
			0 => 'a',
			1 => 'b',
			'slawa' => 'test',
		]);
	}

	public function test_ArrayIterator_foreach()
	{
		$ai = new ArrayIterator($this->ai->getArrayCopy());
		$content = '';
		foreach ($ai as $i => $a) {
			$content .= $a;
		}
		$this->assertEquals('abtest', $content);
	}

	public function test_ArrayIteratorPlus_foreach()
	{
		$content = '';
		foreach ($this->ai as $i => $a) {
			$content .= $a;
		}
		$this->assertEquals('abtest', $content);
	}

}
