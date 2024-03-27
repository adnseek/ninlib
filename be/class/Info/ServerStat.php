<?php

class ServerStat extends AppControllerBE
{
	public $start_time;
	public $LOG = [];
	public $COUNTQUERIES = 0;
	public $totalTime;

	/**
	 * @var Config
	 */
	public $config;

	public function __construct($start_time = null, $LOG = [], $COUNTQUERIES = 0)
	{
		parent::__construct();
		$this->start_time = $start_time ? $start_time : $_SERVER['REQUEST_TIME'];
		$this->LOG = $LOG;
		$this->COUNTQUERIES = $COUNTQUERIES;
		$this->config = Config::getInstance();
	}

	/**
	 * AJAX
	 * @return string
	 */
	public function updateHereAction()
	{
		$content = $this->renderEverything();
		$content .= '<script> updateHere(); </script>';
		return $content;
	}

	public function renderEverything()
	{
		$content = '<div class="col-md-5">';
		$content .= '<fieldset><legend>PHP Info</legend>' . $this->getPHPInfo() . '</fieldset>';

		$s = slTable::showAssoc($this->getPerformanceInfo());
		$s->more = ['class' => "table table-striped table-condensed"];
		$content .= '<fieldset><legend>Performance</legend>' . $s . '</fieldset>';

		$content .= '</div><div class="col-md-5">';

		$content .= '<fieldset><legend>Server Info</legend>
			' . $this->getServerInfo() . '
		</fieldset>';
		$content .= '<fieldset><legend>Query Log</legend>' . $this->getQueryLog() . '</fieldset>';
		$content .= '</div>';
		return $content;
	}

	public function getPHPInfo()
	{
		$useMem = memory_get_usage();
		$allMem = intval(ini_get('memory_limit')) * 1024 * 1024;

		$conf = [];
		$conf['Server'] = $_SERVER['SERVER_NAME'];
		$conf['IP'] = $_SERVER['SERVER_ADDR'];
		$conf['PHP'] = phpversion();
		$conf['Server time'] = date('Y-m-d H:i:s');
		$conf['documentRoot'] = $this->config->documentRoot;
		$conf['appRoot'] = AutoLoad::getInstance()->getAppRoot();
		$conf['nadlibRoot'] = AutoLoad::getInstance()->nadlibRoot;
		$conf['nadlibFromDocRoot'] = AutoLoad::getInstance()->nadlibFromDocRoot;
		$conf['memory_limit'] = number_format($allMem / 1024 / 1024, 3, '.', '') . ' MB';
		$conf['Mem. used'] = number_format($useMem / 1024 / 1024, 3, '.', '') . ' MB';
		$conf['Mem. used %'] = new HTMLTag('td', [
			'style' => 'width: 100px; background: no-repeat url(' . $this->getBarURL($useMem / $allMem * 100) . ');',
		], number_format($useMem / $allMem * 100, 3) . '%');
		$conf['Mem. peak'] = number_format(memory_get_usage() / 1024 / 1024, 3, '.', '') . ' MB';
		if (session_id()) {
			$sessionPath = ini_get('session.save_path');
			$sessionPath = $sessionPath ? $sessionPath : '/tmp';
			$sessionFile = $sessionPath . '/sess_' . session_id();
			//$conf[] = array('param' => 'Session File',		'value' => $sessionFile);
			$conf['Sess. size'] = @filesize($sessionFile);
		}
		$s = slTable::showAssoc($conf);
		$s->more = ['class' => "table table-striped table-condensed"];
		return $s;
	}

	public function getBarURL($percent)
	{
		return AutoLoad::getInstance()->nadlibFromDocRoot . 'bar.php?rating=' . round($percent) . '&!border=0&height=25';
	}

	public function getPerformanceInfo()
	{
		$this->LOG = is_array($this->LOG) ? $this->LOG : [];

		// calculating total sql time
		$totalTime = 0;
		foreach ($this->LOG as $i => $row) {
			$totalTime += $row['total'];
		}
		$totalTime = number_format($totalTime, 3);
		$this->totalTime = $totalTime; // @used getQueryLog

		// reformatting the data for output
		foreach ($this->LOG as $i => $row) {
			$this->LOG[$i]['percent'] = number_format($row['total'] * 100 / $totalTime, 3) . '%';
			$this->LOG[$i]['query'] = '<span title="' . $this->LOG[$i]['title'] . '">' . $this->LOG[$i]['query'] . '</span>';
			$this->LOG[$i]['###TD_CLASS###'] = 'invisible';
		}
		usort($this->LOG, [$this, 'sortLog']);

		$allTime = microtime(true) - $this->start_time;
		$sqlTime = $totalTime;
		$phpTime = $allTime - $sqlTime;

		$conf = [];
		$conf['100% Time'] = number_format($allTime, 3, '.', '');
		$conf['PHP Time'] = number_format($phpTime, 3, '.', '');
		$conf['PHP Time %'] = $this->getBarWith(number_format($phpTime / $allTime * 100, 3, '.', ''));
		$conf['SQL Time'] = number_format($sqlTime, 3, '.', '');
		$conf['SQL Time %'] = $this->getBarWith(number_format($sqlTime / $allTime * 100, 3, '.', ''));
		if ($this->COUNTQUERIES) {
			$conf['Unique Q'] = sizeof($this->LOG) . '/' . $this->COUNTQUERIES;
			$conf['All queries'] = $this->COUNTQUERIES;
			$conf['Unique Q'] = $this->getBar(sizeof($this->LOG) / $this->COUNTQUERIES * 100);
		}
		//debug($conf);
		return $conf;
	}

	public function getBarWith($value)
	{
		return new HTMLTag('td', [
			'style' => 'width: 100px; background: no-repeat url(' . $this->getBarURL($value) . ');',
		], $value . ' %');
	}

	public function getBar($percent)
	{
		return '<img src="' . $this->getBarURL($percent) . '" />';
	}

	public function getServerInfo()
	{
		$conf = [];
		$total = @disk_total_space('/');
		$diskpercent = 0;
		if ($total) {
			$diskpercent = ($total - @disk_free_space('/')) / $total * 100;
		}
		$conf[] = [
			'param' => 'Disk space',
			'value' => number_format($dts = $total / 1024 / 1024 / 1024, 3, '.', '') . ' GB',
		];
		$conf[] = [
			'param' => 'Disk used',
			'value' => number_format($dts - @disk_free_space('/') / 1024 / 1024 / 1024, 3, '.', '') . ' GB',
		];
		//$conf[] = array('param' => 'Disk used',
		//'value' => number_format($diskpercent, 3, '.', '').' %');
		//$conf[] = array('param' => 'Disk used',
		//'value' => $this->getBar($diskpercent));
		$conf[] = [
			'param' => 'Disk used %',
			'value' => new HTMLTag('td', [
				'style' => 'width: 100px; background: no-repeat url(' . $this->getBarURL($diskpercent) . ');',
			], number_format($diskpercent, 3) . '%'),
		];
		$cpu = $this->getCpuUsage();
		//$conf[] = array('param' => 'CPU used',
		//'value' => number_format(100 - $cpu['idle'], 3, '.', '').'%');
		//$conf[] = array('param' => 'CPU used',
		//'value' => $this->getBar(100 - $cpu['idle']));
		$conf[] = [
			'param' => 'CPU used %',
			'value' => new HTMLTag('td', [
				'style' => 'width: 100px; background: no-repeat url(' . $this->getBarURL(100 - $cpu['idle']) . ');',
			], number_format(100 - $cpu['idle'], 3) . '%'),
		];
		$ram = $this->getRAMInfo();
		$conf[] = [
			'param' => 'RAM',
			'value' => number_format($ram['total'] / 1024, 3, '.', '') . ' Mb',
		];
		$conf[] = [
			'param' => 'RAM used',
			'value' => number_format($ram['used'] / 1024, 3, '.', '') . ' Mb',
		];
		//$conf[] = array('param' => 'RAM used',
		//'value' => number_format($ram['percent'], 3, '.', '').'%');
		//$conf[] = array('param' => 'RAM used',
		//'value' => $this->getBar($ram['percent']));
		$conf[] = [
			'param' => 'RAM used %',
			'value' => new HTMLTag('td', [
				'style' => 'width: 100px; background: no-repeat url(' . $this->getBarURL($ram['percent']) . ');',
			], number_format($ram['percent'], 3) . '%'),
		];
		$conf[] = [
			'param' => 'Uptime',
			'value' => $this->format_uptime(implode('', array_slice(explode(" ", @file_get_contents('/proc/uptime')), 0, 1))),
		];
		$conf[] = [
			'param' => 'Server load',
			'value' => implode('', array_slice(explode(' ', @file_get_contents('/proc/loadavg')), 0, 1)),
		];

		$s = new slTable($conf, '', [
			'param' => '',
			'value' => '',
		]);
		$s->more = ['class' => "table table-striped table-condensed"];
		return $s;
	}

	function getCpuUsage($_statPath = '/proc/stat')
	{
		TaylorProfiler::start(__METHOD__);
		$percentages = [
			'idle' => null,
		];
		if (@file_exists($_statPath)) {
			$time1 = $this->getStat($_statPath) or die("getCpuUsage(): couldn't access STAT path or STAT file invalid\n");
			sleep(1);
			$time2 = $this->getStat($_statPath) or die("getCpuUsage(): couldn't access STAT path or STAT file invalid\n");
			//debug($time1, $time2);

			$delta = [];

			foreach ($time1 as $k => $v) {
				$delta[$k] = $time2[$k] - $v;
			}

			$deltaTotal = array_sum($delta);

			foreach ($delta as $k => $v) {
				$percentages[$k] = $v / $deltaTotal * 100;
			}
		}
		TaylorProfiler::stop(__METHOD__);
		return $percentages;
	}

	protected function getStat($_statPath = '/proc/stat')
	{
		$stat = @file_get_contents($_statPath);

		if (substr($stat, 0, 3) == 'cpu') {
			$parts = explode(" ", preg_replace("!cpu +!", "", $stat));
		} else {
			return false;
		}

		$return = [];
		$return['user'] = $parts[0];
		$return['nice'] = $parts[1];
		$return['system'] = $parts[2];
		$return['idle'] = $parts[3];
		return $return;
	}

	public function getRAMInfo()
	{
		$meminfo = "/proc/meminfo";
		if (@file_exists($meminfo)) {
			$mem = file_get_contents($meminfo);
			$totalp = 0;
			if (preg_match('/MemTotal\:\s+(\d+) kB/', $mem, $matches)) {
				$totalp = $matches[1];
			}
			unset($matches);

			$freep = 0;
			if (preg_match('/MemFree\:\s+(\d+) kB/', $mem, $matches)) {
				$freep = $matches[1];
			}
			$freiq = $freep;
			$insgesamtq = $totalp;
			$belegtq = $insgesamtq - $freiq;
			$prozent_belegtq = 100 * $belegtq / $insgesamtq;
		}
		$res = [
			'total' => ifsetor($totalp),
			'used' => ifsetor($belegtq),
			'free' => ifsetor($freiq),
			'percent' => ifsetor($prozent_belegtq),
		];
		return $res;
	}

	public function format_uptime($seconds)
	{
		$secs = intval($seconds % 60);
		$mins = intval($seconds / 60 % 60);
		$hours = intval($seconds / 3600 % 24);
		$days = intval($seconds / 86400);

		$uptimeString = $days . "D ";
		$uptimeString .= str_pad($hours, 2, '0', STR_PAD_LEFT) . ":";
		$uptimeString .= str_pad($mins, 2, '0', STR_PAD_LEFT) . ":";
		$uptimeString .= str_pad($secs, 2, '0', STR_PAD_LEFT);
		return $uptimeString;
	}

	public function getQueryLog()
	{
		$s = new slTable('dumpQueries', 'width="100%"');
		$s->thes([
			'query' => ['name' => 'Query', 'no_hsc' => true, 'colspan' => 7, 'new_tr' => true],
			'function' => '<a href="javascript: void(0);" onclick="toggleRows(\'dumpQueries\');">Func.</a>',
			'line' => '(l)',
			//'results' => 'Rows',
			'elapsed' => ['name' => '1st', 'decimals' => 3],
			'count' => '#',
			'total' => ['name' => $this->totalTime, 'decimals' => 3],
			'percent' => '100%',
		]);
		$s->data = ifsetor($this->LOG, $this->config->getDB()->getQueryLog());
		$s->isOddEven = true;
		$s->more = ['class' => "nospacing"];
		return $s;
	}

	public function sortLog($a, $b)
	{
		$a = $a['total'];
		$b = $b['total'];
		if ($a == $b) {
			$aa = $a['query'];
			$bb = $b['query'];
			if ($aa == $bb) {
				return 0;
			} elseif ($aa < $bb) {
				return 1;
			} else {
				return -1;
			}
		} elseif ($a < $b) {
			return 1;
		} else {
			return -1;
		}
	}

	public function __toString()
	{
		return $this->s($this->render());
	}

	public function render()
	{
		$this->index->addJS('be/js/main.js');
		$content = $this->performAction();
		if (!$content) {
			$content = '<div
				id="div_SystemInfo"
				class="row updateHere"
				src="?c=ServerStat&ajax=1&action=updateHere">' . $this->renderEverything() . '</div>';

		}
		return $content;
	}

}
