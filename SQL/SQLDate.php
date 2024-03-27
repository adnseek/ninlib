<?php

class SQLDate extends Date
{

	/**
	 * @var Date
	 */
	protected $date;

	public function __construct(Date $d)
	{
		$this->date = $d;
	}

	public function __toString()
	{
		return $this->date->format('Y-m-d');
	}

	public function debug()
	{
		return $this->__toString();
	}

}
