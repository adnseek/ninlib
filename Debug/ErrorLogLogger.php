<?php

class ErrorLogLogger
{

	public function log($method, $data)
	{
		if (is_array($data)) {
			$output = print_r($data, true);
		} elseif (is_scalar($data)) {
			$output = '<' . gettype($data) . '> ' . $data;
		} elseif (is_object($data)) {
			$output = get_class($data);
		} else {
			$output = json_encode($data);
		}
		error_log('[' . $method . '] ' . $output);
	}

	public function info($method, $data)
	{
		$this->log($method, $data);
	}

}
