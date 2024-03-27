<?php /** @noinspection ForgottenDebugOutputInspection */
/********************************************************************************\
 * Copyright (C) Carl Taylor (cjtaylor@adepteo.com)                             *
 * Copyright (C) Torben Nehmer (torben@nehmer.net) for Code Cleanup             *
 *                                                                              *
 * This program is free software; you can redistribute it and/or                *
 * modify it under the terms of the GNU General Public License                  *
 * as published by the Free Software Foundation; either version 2               *
 * of the License, or (at your option) any later version.                       *
 *                                                                              *
 * This program is distributed in the hope that it will be useful,              *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of               *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                *
 * GNU General Public License for more details.                                 *
 *                                                                              *
 * You should have received a copy of the GNU General Public License            *
 * along with this program; if not, write to the Free Software                  *
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.  *
 * \********************************************************************************/

/// Enable multiple timers to aid profiling of performance over sections of code

class TaylorProfiler
{
	/**
	 * @var SplObjectStorage
	 */
	static public $sos;
	/**
	 * @var TaylorProfiler
	 */
	static public $instance;
	public $description;
	public $description2;
	public $startTime;
	public $endTime;
	public $initTime;
	public $cur_timer;
	public $stack;
	public $trail;
	public $trace;
	public $count;
	public $running;
	public $output_enabled;
	public $trace_enabled;
	public $isLog = false;

	/**
	 * Initialise the timer. with the current micro time
	 * @param bool $output_enabled
	 * @param bool $trace_enabled
	 */
	public function __construct($output_enabled = false, $trace_enabled = false)
	{
		$this->description = [];
		$this->startTime = [];
		$this->endTime = [];
		$this->cur_timer = "";
		$this->stack = [];
		$this->trail = "";
		$this->trace = [];
		$this->count = [];
		$this->running = [];
		$this->initTime = $this->getMicroTime();
		//$this->initTime = $_SERVER['REQUEST_TIME'];	// since PHP 5.4.0
		//debug($this->initTime);
		$this->output_enabled = $output_enabled;
		$this->trace_enabled = $trace_enabled;
		$this->startTimer('unprofiled');
		self::$instance = $this;
	}

	/**
	 * Get the current time as accurately as possible
	 */
	public function getMicroTime()
	{
		return microtime(true);
	}

	/**
	 *   Start an individual timer
	 *   This will pause the running timer and place it on a stack.
	 * @param string $name name of the timer
	 * @param string $desc description of the timer
	 */
	public function startTimer($name = null, $desc = "")
	{
		$name = $name ?: $this->getName();
		if ($this->trace_enabled) {
			$this->trace[] = [
				'time' => time(),
				'function' => $name . " {",
				'memory' => memory_get_usage(),
			];
		}
		if ($this->output_enabled) {
			$n = array_push($this->stack, $this->cur_timer);
			$this->suspendTimer($this->stack[$n - 1]);
			$this->startTime[$name] = $this->getMicroTime();
			$this->cur_timer = $name;
			$this->description[$name] = $desc;
			if (!array_key_exists($name, $this->count)) {
				$this->count[$name] = 1;
			} else {
				$this->count[$name]++;
			}
			if (false) {
				$hash = md5($name);
				$hash = substr($hash, 0, 6);
				echo '<span style="background: #' . $hash . '">', $name,
				' START', '</span>', BR;
			}
		}
	}

	public static function enableLog()
	{
		$self = self::getInstance();
		$self->isLog = true;
	}

	public static function getName()
	{
		if (class_exists('Debug') && method_exists('Debug', 'getCaller')) {
			$name = Debug::getCaller(3);    // three is best
		} elseif (class_exists('dbLayerPG')) {
			$name = dbLayerPG::getCaller(3, 2);
		} else {
			$name = 'noname';
		}
		return $name;
	}

	/**
	 * suspend  an individual timer
	 * @param string $name
	 */
	public function suspendTimer($name)
	{
		$this->trace[] = ['time' => time(), 'function' => "$name }...", 'memory' => memory_get_usage()];
		$this->endTime[$name] = $this->getMicroTime();
		if (!array_key_exists($name, $this->running)) {
			$this->running[$name] = $this->elapsedTime($name);
		} else {
			$this->running[$name] += $this->elapsedTime($name);
		}
	}

	/**
	 *   measure the elapsed time of a timer without stoping the timer if
	 *   it is still running
	 * @param string $name
	 * @return int|mixed
	 */
	public function elapsedTime($name)
	{
		// This shouldn't happen, but it does once.
		if (!array_key_exists($name, $this->startTime))
			return 0;

		if (array_key_exists($name, $this->endTime)) {
			return ($this->endTime[$name] - $this->startTime[$name]);
		}

		$now = $this->getMicroTime();
		return ($now - $this->startTime[$name]);
	}//end start_time

	public static function getMemoryUsage()
	{
		static $max;
		static $previous;
		$memLimit = new Bytes(ini_get('memory_limit'));
		$max = $max ?: $memLimit->getBytes();
		$maxMB = number_format($max / 1024 / 1024, 0, '.', '');
		$cur = memory_get_usage(true);
		$usedMB = number_format($cur / 1024 / 1024, 3, '.', '');
		$percent = $maxMB != 0
			? number_format($usedMB / $maxMB * 100, 3, '.', '')
			: '';
		$content = str_pad($usedMB, 8, ' ', STR_PAD_LEFT)
			. '/' . $maxMB . 'MB '
			. str_pad($percent, 8, ' ', STR_PAD_LEFT) . '% ';

		$content .= '[' . str_repeat('=', $percent / 4) .
			str_repeat('-', 25 - $percent / 4) .
			']';

		$increase = $usedMB - $previous;
		$sign = $increase > 0 ? '+' : ' ';
		$content .= ' (' . $sign . number_format($increase, 3, '.', '') . ' MB)';

		$previous = $usedMB;
		return $content;
	}//end start_time

	public static function addMemoryMap($obj)
	{
		self::$sos = self::$sos ? self::$sos : new SplObjectStorage();
		self::$sos->attach($obj);
	}

	public static function getMemoryMap()
	{
		$table = [];
		foreach (self::$sos as $obj) {
			$class = get_class($obj);
			$table[$class]['count']++;
			$table[$class]['mem1'] = strlen(serialize($obj));
			$table[$class]['memory'] += $table[$class]['mem1'];
		}
		return $table;
	}

	/**
	 * @return string
	 */
	public static function getElapsedTimeString()
	{
		$totalTime = self::getElapsedTime();
		list($seconds, $ms) = explode('.', $totalTime);
		$totalTime = gmdate('H:i:s', $seconds) . '.' . $ms;
		return $totalTime;
	}

	/**
	 * @return float
	 */
	public static function getElapsedTime()
	{
		$profiler = self::getInstance();
		if ($profiler) {
			$since = $profiler->initTime;
		} else {
			$since = $_SERVER['REQUEST_TIME_FLOAT']
				? $_SERVER['REQUEST_TIME_FLOAT']
				: $_SERVER['REQUEST_TIME'];
		}
		$oaTime = microtime(true) - $since;
		return number_format($oaTime, 3, '.', '');
	}

	/**
	 * @param bool $output_enabled
	 * @param bool $trace_enabled
	 * @return null|TaylorProfiler
	 */
	public static function getInstance($output_enabled = false, $trace_enabled = false)
	{
		return ifsetor($GLOBALS['profiler']) instanceof self
			? $GLOBALS['profiler']
			: (
			self::$instance
				?: self::$instance = new self($output_enabled, $trace_enabled)
			);
	}

	/// Internal Use Only Functions

	public static function renderFloat($withCSS = true)
	{
		$ft = new FloatTime($withCSS);
		return $ft->render();
	}

	/**
	 * @return float [0.0 .. 1.0]
	 */
	public static function getMemUsage()
	{
		require_once __DIR__ . '/../Value/Bytes.php';
		$memory_limit = ini_get('memory_limit');
		$memLimit = new Bytes($memory_limit);
		$max = $memLimit->getBytes();
		$cur = memory_get_usage();
		//echo $cur, '/', $max, TAB, $memory_limit, TAB, $memLimit, BR;
		return number_format($cur / $max, 3, '.', '');
	}

	public static function getTimeUsage()
	{
		static $max;
		$max = $max ? $max : intval(ini_get('max_execution_time'));
		$cur = microtime(true) - $_SERVER['REQUEST_TIME'];
		return number_format($cur / $max, 3, '.', '');
	}

	public static function getMemDiff()
	{
		static $prev = 0;
		$cur = memory_get_usage();
		$diff = ($cur - $prev) / 1024 / 1024;
		$return = ($diff > 0 ? '+' : '') . number_format($diff, 3, '.', '') . 'M';
		$prev = $cur;
		return $return;
	}

	public static function dumpQueries()
	{
		$queryLog = class_exists('Config', false)
			? (Config::getInstance()->getDB()
				? Config::getInstance()->getDB()->getQueryLog()
				: null)
			: null;
		if (DEVELOPMENT && $queryLog) {
			return $queryLog->dumpQueriesTP();
		}
		return null;
	}

	public static function start($method = null)
	{
		$method = $method ?: self::getName();
		$tp = TaylorProfiler::getInstance();
		if ($tp->isLog) {
			llog(strip_tags(str_replace(PHP_EOL, ' >> ', $method)));
			$tp->startTimer($method);
		}
	}

	public static function stop($method = null)
	{
		$method = $method ?: self::getName();
		$tp = TaylorProfiler::getInstance();
		$tp ? $tp->stopTimer($method) : null;
	}

	public static function dumpMemory($var, $path = [])
	{
		static $visited = [];
		if (is_array($var)) {
			$log = implode('', [
				implode('', $path),
				'[',
				count($var),
				']',
				BR,
			]);
			error_log($log);
			echo $log;
			foreach ($var as $key => $val) {
				if (!is_scalar($val) && $key !== 'GLOBALS') {
					$newPath = array_merge($path, ['.' . $key]);
					self::dumpMemory($val, $newPath);
				}
			}
		} elseif (is_object($var)) {
			if (in_array(spl_object_hash($var), $visited)) {
				echo implode('', $path), ' *RECURSION*', PHP_EOL;
			} else {
				$visited[] = spl_object_hash($var);
				$objVars = get_object_vars($var);
				$log = implode('', [
					implode('', $path),
					'{',
					count($objVars),
					'}',
					BR,
				]);
				error_log($log);
				echo $log;
				foreach ($objVars as $key => $val) {
					if (!is_scalar($val)) {
						$newPath = array_merge($path, ['->' . $key]);
						self::dumpMemory($val, $newPath);
					}
				}
			}
		}
	}

	public function clearMemory()
	{
		$this->description = [];
		$this->startTime = [];
		$this->endTime = [];
		$this->cur_timer = "";
		$this->stack = [];
		$this->trail = "";
		$this->trace = [];
		$this->count = [];
		$this->running = [];
		$this->trace_enabled = false;
		$this->output_enabled = false;
	}

	/**
	 *   Measure the elapsed time since the profile class was initialised
	 *
	 */
	public function elapsedOverall()
	{
		return $this->getMicroTime() - $this->initTime;
	}

	/**
	 * Print out a log of all the timers that were registered
	 * @param bool $enabled
	 * @return null|string
	 */
	public function printTimers($enabled = false)
	{
		if ($this->output_enabled || $enabled) {
			$this->stopTimer('unprofiled');
			$tot_perc = 0;
			ksort($this->description);
			$oaTime = $this->getMicroTime() - $this->initTime;

			$together = [];
			foreach ($this->description as $key => $val) {
				$row = [];
				$row['desc'] = $val;
				$row['time'] = $this->elapsedTime($key);
				$row['total'] = ifsetor($this->running[$key]);
				$row['count'] = $this->count[$key];
				$row['avg'] = $row['total'] * 1000 / $row['count'];
				$row['perc'] = ($row['total'] / $oaTime) * 100;
				$together[$key] = $row;
				if ($key === 'unprofiled') {
					$together[$key]['bold'] = true;
					$together[$key]['desc'] = 'Between new TaylorProfiler() and printTimers()';
				}
			}

			// add missing
			$TimedTotal = 0;
			foreach ($together as $row) {
				$TimedTotal += $row['total'];
			}
			$missed = $oaTime - $TimedTotal;
			$perc = ($missed / $oaTime) * 100;
			$tot_perc += $perc;
			$together['Missed between the calls'] = [
				'desc' => 'Missed between the calls (' . $oaTime . '-' . $TimedTotal . '[' . count($together) . '])',
				'bold' => true,
				'time' => number_format($missed, 2, '.', ''),
				'total' => number_format($missed, 2, '.', ''),
				'count' => 0,
				'perc' => number_format($perc, 2, '.', ''),
			];

			if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
				$requestTime = $_SERVER['REQUEST_TIME_FLOAT']
					? $_SERVER['REQUEST_TIME_FLOAT']
					: $_SERVER['REQUEST_TIME'];
				$startup = $this->initTime - $requestTime;
				$together['Startup'] = [
					'desc' => 'Startup (REQUEST_TIME_FLOAT) (' . $this->initTime . '-' . $requestTime . ')',
					'bold' => true,
					'time' => number_format($startup, 2, '.', ''),
					'total' => number_format($startup, 2, '.', ''),
					'count' => 1,
					'perc' => number_format($startup / $oaTime * 100, 2, '.', ''),
				];
			}

			uasort($together, [$this, 'sort']);

			$table = [];
			$i = 0;
			foreach ($together as $key => $row) {
				$total = $row['total'];
				$TimedTotal += $total;
				$perc = $row['perc'];
				$tot_perc += $perc;

				$htmlKey = $key;
				if (!Request::isCLI() && ifsetor($row['bold'])) {
					$htmlKey = '<b>' . $key . '</b>';
				}
				// used as mouseover
				$desc = ifsetor($this->description2[$key], $row['desc']);
				if (Request::isCLI()) {
					$routine = $desc ?: $key;
					$routine = first(trimExplode("\n", $routine));
				} else {
					$routine = '<span title="' . htmlspecialchars($desc, ENT_QUOTES) . '">' . $htmlKey . '</span>';
				}
				$table[] = [
					'nr' => ++$i,
					'count' => $row['count'],
					'time, ms' => number_format($total * 1000, 2, '.', '') . '',
					'avg/1' => number_format(ifsetor($row['avg']), 2, '.', '') . '',
					'percent' => is_numeric($perc)
						? number_format($perc, 2, '.', '') . '%'
						: $perc,
					'bar' => is_numeric($perc)
						? ProgressBar::getImage($perc)
						: null,
					'routine' => $routine,
				];
			}

			$s = new slTable($table, 'class="nospacing no-print table" width="100%"');
			$s->thes([
				'nr' => 'nr',
				'count' => [
					'name' => 'count',
					'align' => 'right',
				],
				'time, ms' => [
					'name' => 'time, ms',
					'align' => 'right',
				],
				'avg/1' => [
					'name' => 'avg/1',
					'align' => 'right',
				],
				'percent' => [
					'name' => 'percent',
					'align' => 'right',
				],
				'bar' => [
					'no_hsc' => true,
				],
				'routine' => [
					'name' => 'routine',
					'no_hsc' => true,
					'wrap' => new Wrap('<small>|</small>'),
				],
			]);
			$s->isOddEven = true;
			$s->footer = [
				'nr' => 'total',
				'time, ms' => number_format($oaTime * 1000, 2, '.', ''),
				'percent' => number_format($tot_perc, 2, '.', '') . '%',
				'routine' => "OVERALL TIME (" . number_format(memory_get_peak_usage() / 1024 / 1024, 3, '.', '') . "MB)",
			];
			return Request::isCLI()
				? $s->getCLITable(true)
				: $s->getContent();
		}
		return null;
	}

	/**
	 *   Stop an individual timer
	 *   Restart the timer that was running before this one
	 * @param string $name name of the timer
	 */
	public function stopTimer($name = null)
	{
		$name = $name ?: $this->getName();
		if ($this->trace_enabled) {
			$this->trace[] = ['time' => time(), 'function' => "$name }", 'memory' => memory_get_usage()];
		}
		if ($this->output_enabled) {
			$this->endTime[$name] = $this->getMicroTime();
			if (!array_key_exists($name, $this->running)) {
				$this->running[$name] = $this->elapsedTime($name);
			} else {
				$this->running[$name] += $this->elapsedTime($name);
			}
			$this->cur_timer = array_pop($this->stack);
			$this->resumeTimer($this->cur_timer);
			if (false) {
				$hash = md5($name);
				$hash = substr($hash, 0, 6);
				echo '<span style="background: #' . $hash . '">', $name,
				' STOP', '</span>', BR;
			}
		}
	}

	/**
	 * resume  an individual timer
	 * @param string $name
	 */
	public function resumeTimer($name)
	{
		$this->trace[] = ['time' => time(), 'function' => "$name {...", 'memory' => memory_get_usage()];
		$this->startTime[$name] = $this->getMicroTime();
	}

	public function getCSS()
	{
		$content = '';
		if (!Request::isCLI()) {
			$content .= '<style>' . file_get_contents(
					__DIR__ . '/../CSS/TaylorProfiler.css'
				) . '</style>';
		}
		return $content;
	}

	public function sort($a, $b)
	{
		$a = $a['perc'];
		$b = $b['perc'];
		if ($a > $b) return -1;
		if ($a < $b) return +1;
		if ($a == $b) return 0;
	}

	public function printTrace($enabled = false)
	{
		if ($this->trace_enabled || $enabled) {
			$prev = 0;
			$prevt = $this->trace[0]['time'];
			foreach ($this->trace as $i => $trace) {
				$this->trace[$i]['time'] = date('i:s', $trace['time'] - $prevt);
				$this->trace[$i]['diff'] = number_format(($trace['memory'] - $prev) / 1024, 1, '.', ' ') . ' KB';
				$this->trace[$i]['memory'] = number_format(($trace['memory']) / 1024, 1, '.', ' ') . ' KB';
				$prev = $trace['memory'];
			}
			return new slTable($this->trace);
		}
	}

	public function analyzeTraceForLeak()
	{
		$func = [];
		foreach ($this->trace as $i => $trace) {
			$func[$trace['function']]++;
		}
		ksort($func);
		return slTable::showAssoc($func);
	}

	public function getMaxMemory()
	{
		$ret = null;
		$amem = ArrayPlus::create($this->trace)->column('memory');
		if (sizeof($amem)) {
			$ret = max($amem);
		}
		return $ret;
	}

}
