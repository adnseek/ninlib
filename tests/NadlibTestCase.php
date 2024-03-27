<?php

class NadlibTestCase extends PHPUnit\Framework\TestCase
{

	public $canPrint = false;

	public function log(...$something)
	{
		if (!$this->canPrint) {
			return;
		}
		echo implode(TAB, $something), PHP_EOL;
	}


	public function assertEqualsIngnoreSpaces($must, $is)
	{
		$must = $this->normalize($must);
		$is = $this->normalize($is);
		$this->assertEquals($must, $is);
	}

	public function normalize($s)
	{
		return implode(PHP_EOL, trimExplode("\n", $s));
	}

}
