<?php

namespace nadlib;

class Service
{

	/**
	 * @var IndexInterface
	 */
	protected $index;

	public function __construct(IndexInterface $index)
	{
		$this->index = $index;
	}

}
