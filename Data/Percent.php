<?php

class Percent
{

	protected $top;

	protected $i = 0;

	public function __construct($top = 100)
	{
		$this->top = $top;
	}

	public function i()
	{
		return $this->i;
	}

	public function inc()
	{
		$this->i++;
	}

	public function get($decimals = 2)
	{
		return number_format($this->i / $this->top * 100, $decimals);
	}

	public function changed($decimals = 2)
	{
		static $last;
		if ($last != $this->get($decimals)) {
			$last = $this->get($decimals);
			return true;
		}
		return false;
	}

}
