<?php

class UnitTestBase
{

	public function error($message)
	{
		error_log($message);
	}

	public function assertEquals($must, $is, $message = 'assertEquals')
	{
		if ($must != $is) {
			$this->error($message);
			debug($is);
		}
	}

	public function assertCount($must, iterable $array, $message = 'assertCount')
	{
		if (count($array) != $must) {
			$this->error($message);
		}
	}

	public function assertTrue($check, $message = 'assertTrue')
	{
		if (!$check) {
			$this->error($message);
		}
	}

}
