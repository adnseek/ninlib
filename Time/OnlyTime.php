<?php

class OnlyTime extends Time
{

	public function __construct($input = null, $relativeTo = null)
	{
		parent::__construct($input, $relativeTo);
		$this->modify('1970-01-01 H:i:s');
		$this->updateDebug();
		//debug($this);
	}

	public function getMySQL()
	{
		return gmdate('H:i:s', $this->time);
	}

}
