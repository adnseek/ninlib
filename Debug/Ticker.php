<?php

//declare(ticks=100);
require_once __DIR__ . '/TaylorProfiler.php';

/**
 * Class Ticker
 * @phpstan-consistent-constructor
 */
class Ticker
{

	static public $instance;

	/**
	 * @var string "html"
	 * or "header" for X-Tick header
	 * or "errorlog" or "echo"
	 */
	public $tickTo = 'html';

	/**
	 * Ticks between output
	 * @var int
	 */
	public $tickTime = 1000;

	/**
	 * @var int
	 */
	public $prevMemory = 0;

	/**
	 * Tells if stopOutput() was called
	 * @var bool
	 */
	public $noOutput = false;

	/**
	 * How often tick has interrupted a particular function
	 * @var array
	 */
	public $functionCount = [];

	/**
	 * The first time the function is called
	 * @var array
	 */
	public $firstCall = [];

	/**
	 * @var array
	 */
	public $lastCall = [];

	public function __construct()
	{
		self::$instance = $this;
		$isCLI = $this->isCLI();
		$this->tickTo = $isCLI ? 'echo' : 'html';
		if (!defined('BR')) {
			if ($isCLI) {
				define('BR', "\n");
			} else {
				define('BR', "<br />\n");
			}
		}
		if (!defined('TAB')) {
			define('TAB', "\t");
		}

		$this->prevMemory = TaylorProfiler::getMemUsage();
	}

	/**
	 * @return bool
	 */
	public function isCLI()
	{
		$isCLI = php_sapi_name() == 'cli';
		return $isCLI;
	}

	/**
	 * @return self
	 */
	public static function getInstance()
	{
		return self::$instance ?: self::$instance = new static();
	}

	public static function enableTick($ticker = 1000, $func = null)
	{
		$tp = self::getInstance();
		$ok = register_tick_function($func ?: [$tp, 'tick']);
		if ($ok) {
			$tp->tickTime = $ticker;
			//$tp->tick();
			//echo 'Ticker is enabled', BR;
		} else {
			die('register_tick_function returned false');
		}
		return $tp;
	}

	/**
	 * This is not working reliably yet. Stops output forever
	 * @deprecated
	 */
	public function stopOutput()
	{
		ob_start([$this, 'ob_end']);
		$this->noOutput = true;
	}

	public function ob_end($output)
	{
		// don't print
		return 'Collected output length: ' . strlen($output) . BR;
	}

	/**
	 * @throws Exception
	 */
	public function tick()
	{
		$bt = debug_backtrace();
		$list = [];
		$prow = [];
		foreach ($bt as $row) {
			$list[] = basename(ifsetor($prow['file'])) .
				((isset($row['object'])
					&& ifsetor($row['file']) != 'class.' . get_class($row['object']) . '.php')
					? ('[' . get_class($row['object']) . ']')
					: ('[' . ifsetor($row['class']) . ']')
				) . '::' . $row['function'] .
				'#' . ifsetor($prow['line']);
			$prow = $row;
		}
		$list = array_reverse($list);
		$list = array_slice($list, 0, -1);    // cut TaylorProfiler::tick
		//$list = array_slice($list, 3);
		$lastCall = end($list);
		if ($lastCall) {
			$lastCall = first(trimExplode('#', $lastCall));
			$this->functionCount[$lastCall] = ifsetor($this->functionCount[$lastCall], 0) + 1;
			if (!isset($this->firstCall[$lastCall])) {
				$this->firstCall[$lastCall] = microtime(true);
			}
			$this->lastCall[$lastCall] = microtime(true);
		}
		$trace = implode(' -> ', $list);
		$trace = substr($trace, -100);

		$mem = TaylorProfiler::getMemUsage();
		$diff = number_format(($mem - $this->prevMemory), 3);
		$diff = $diff === 0
			? '<font color="green"> ' . $diff . '</font>'
			: ($diff >= 0
				? '<font color="green">+' . $diff . '</font>'
				: '<font color="red">' . $diff . '</font>');

		$start = ifsetor($_SERVER['REQUEST_TIME_FLOAT'], $_SERVER['REQUEST_TIME']);
		$time = number_format(microtime(true) - $start, 3, '.', '');

		$output = '<pre style="margin: 0; padding: 0;">' .
			tabify([
				'Time: ' . $time,
				'Diff: ' . $diff,
				number_format($mem * 100, 2) . '% mem',
				$trace
			]) . '</pre>';

		$this->render($output, $time);
		$this->prevMemory = $mem;
		if (sizeof($list) > 100) {
			pre_print_r($list);
			throw new Exception('Infinite loop detected');
		}
	}

	public function render($output, $time)
	{
		if ($this->tickTo == 'html') {
			if ($this->isCLI()) {
				$output = strip_tags($output);
			}
			echo $output . "\n";
		} elseif ($this->tickTo == 'header') {
			$pad = str_pad($time, 6, '0', STR_PAD_LEFT);
			header('X-Tick-' . $pad . ': ' . strip_tags($output));
		} elseif ($this->tickTo == 'errorlog') {
			error_log(strip_tags($output));
		} elseif ($this->tickTo == 'echo') {
			if ($this->noOutput) {
//				ob_end_clean();
//				ob_end_flush();
			}
			echo strip_tags($output), BR;
			if ($this->noOutput) {
				$this->stopOutput();
			}
		} else {
			echo '.';
		}
	}

	public static function disableTick()
	{
		echo __METHOD__, BR;
		$tp = self::getInstance();
		unregister_tick_function([$tp, 'tick']);
	}

	public function __destruct()
	{
		arsort($this->functionCount);
		foreach ($this->functionCount as $function => $count) {
			if ($this->firstCall[$function] && $this->lastCall[$function]) {
				$duration = $this->lastCall[$function] - $this->firstCall[$function];
				$duration = number_format($duration, 3);
			} else {
				$duration = '';
			}
			echo $count, TAB, $duration, TAB, $function, BR;
		}
	}

}
