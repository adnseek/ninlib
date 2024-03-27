<?php

class MockIndexDCI
{

	public function error($message)
	{
		error_log(__METHOD__ . ': ' . $message);
	}

	public function renderException(Exception $e)
	{
		error_log('>> ' . get_class($e));
		error_log($e->getMessage());
	}

}
