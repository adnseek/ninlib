<?php

/**
 * Class Profiler
 * Usage:
 * $p1 = new Profiler();
 * sleep(1);
 * echo $p1->elapsed();
 *
 * $p2 = new Profiler();
 * sleep(1);
 * $p2->stop();
 * sleep(1);
 * echo $p2->elapsed();
 * @phpstan-consistent-constructor
 */
class Profiler
{

	public $startTime;

	public $endTime;

	public function __construct($startTime = null)
	{
		$this->startTime = $startTime ? $startTime : microtime(true);
	}

	public function restart()
	{
		$this->startTime = microtime(true);
		$this->endTime = null;
	}

	public function stop()
	{
		$this->endTime = microtime(true);
	}

	/**
	 * Stops the timer so the elapsed value is preserved.
	 * @return float
	 */
	public function elapsed()
	{
		if (!$this->endTime) {
			$this->stop();
		}
		$out = $this->endTime - $this->startTime;
		return number_format($out, 5, '.', '');
	}

	/**
	 * Returns elapsed time without stopping the timer. Can be checked in a loop.
	 * @return string
	 */
	public function elapsedCont()
	{
		$out = microtime(true) - $this->startTime;
		return number_format($out, 5, '.', '');
	}

	/**
	 * Restarts the timer, useful for something similar to setTimeout()
	 * @return float
	 */
	public function elapsedNext()
	{
		$since = $this->elapsed();
		$this->restart();
		return $since;
	}

	public function Done($isReturn = false)
	{
		$out = number_format($this->elapsed(), 3);
		$content = "Done in $out seconds." . BR;
		if ($isReturn) {
			return $content;
		} else {
			print($content);
		}
	}

	public function startTimer($method)
	{
		TaylorProfiler::start($method);
	}

	public function stopTimer($method)
	{
		TaylorProfiler::stop($method);
	}

	public function __toString()
	{
		return $this->elapsed() . '';
	}

	public static function sinceStart()
	{
		$p = new static(ifsetor($_SERVER['REQUEST_TIME_FLOAT'], $_SERVER['REQUEST_TIME']));
		return $p->elapsed();
	}

}
