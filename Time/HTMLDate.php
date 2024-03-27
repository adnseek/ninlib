<?php

class HTMLDate extends DateTime
{

	public function __toString()
	{
		return new HTMLTag('time', [
				'datetime' => $this->format('Y-m-d H:i:s'),
				'title' => $this->format('Y-m-d H:i:s'),
			], $this->format('Y-m-d H:i')) . '';
	}

}
