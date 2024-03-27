<?php

class Tail extends AppController
{

	public function render()
	{
		$log = ini_get('error_log');
		if (!$log) {
			$log = '/var/log/apache2/error_log';
			$log = is_file($log) ? $log : null;
		}
		if (!$log) {
			$log = 'z:\\.sys\\apache2\\logs\\error.log';
			echo $log, BR;
			$log = is_file($log) ? $log : null;
		}
		echo 'log: ', $log, BR;
		if ($log) {
			passthru('tail -f ' . $log);
		}
	}

}
