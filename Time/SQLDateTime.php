<?php

class SQLDateTime extends Time
{

	public function __toString()
	{
		return $this->format('Y-m-d H:i:s');
	}

}
