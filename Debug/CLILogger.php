<?php

class CLILogger
{

	public function log($method, $data = null)
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
		echo '[', $method, '] ', $output, BR;
	}

	public function info($method, $data)
	{
		$this->log($method, $data);
	}

}
