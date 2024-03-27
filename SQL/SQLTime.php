<?php

class SQLTime
{
	/**
	 * @var Time
	 */
	protected $time;

	public function __construct(Time $t)
	{
		$this->time = $t;
	}

	public function __toString()
	{
		return $this->time->format('Y-m-d H:i:s');
	}

}
