<?php

class IteratorArrayAccessTest extends ArrayIteratorPlusTest
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

}
