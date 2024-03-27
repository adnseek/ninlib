<?php

class MemoryTicker extends Ticker
{

	public $isFirstTick = true;

	public function tick()
	{
		if ($this->isFirstTick) {
			$this->isFirstTick = false;
			error_log($_SERVER['REQUEST_URI']);
		}
		$mem = TaylorProfiler::getMemUsage();
		if ($mem - $this->prevMemory > 0.1) {
			error_log('Memory: ' . $mem);
			ob_start();
			debug_print_backtrace();
			$bt = ob_get_clean();
			error_log($bt);
			$this->prevMemory = $mem;
		}
		if ($mem > 0.7) {
			error_log($mem);
			echo '<pre>', PHP_EOL;
			debug_print_backtrace();
			die;
		}
	}

}
