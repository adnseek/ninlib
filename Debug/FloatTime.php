<?php

use nadlib\HTTP\Session;

class FloatTime
{

	public $withCSS;

	public function __construct($withCSS)
	{
		$this->withCSS = $withCSS;
	}

	public function render()
	{
		if (Request::isCLI()) {
			return '';
		}
		$totalTime = TaylorProfiler::getElapsedTime();
		$dbTime = $this->getDBTime();
		if (Session::isActive()) {
			$_SESSION[__CLASS__] = ifsetor($_SESSION[__CLASS__], []);
			if (is_scalar($_SESSION[__CLASS__])) {
				$_SESSION[__CLASS__] = [];
			}
			// total
			$totalMax = floatval(ifsetor($_SESSION[__CLASS__]['totalMax'], 0));
			if ($totalMax > 0) {
				$totalBar = '<img src="' . ProgressBar::getBar($totalTime / $totalMax * 100) . '" />';
			} else {
				$totalBar = '<img src="' . ProgressBar::getBar(0) . '" />';
			}
			$_SESSION[__CLASS__]['totalMax'] = max(ifsetor($_SESSION[__CLASS__]['totalMax']), $totalTime);

			// db
			$dbMax = ifsetor($_SESSION[__CLASS__]['dbMax']);
			if ($dbMax > 0) {
				$dbBar = '<img src="' . ProgressBar::getBar($dbTime / $dbMax * 100) . '" />';
			} else {
				$db = class_exists('Config')
					? Config::getInstance()->getDB() : null;
				$ql = $db ? $db->getQueryLog() : null;
				$dbBar = $ql ? sizeof($ql->queryLog) : typ($ql);
			}
			$_SESSION[__CLASS__]['dbMax'] = max(ifsetor($_SESSION[__CLASS__]['dbMax']), $dbTime);
		} else {
			$totalBar = 'no session';
			$totalMax = '';
			$dbTime = '';
			$dbBar = 'no session';
			$dbMax = '';
		}

		$peakMem = number_format(memory_get_peak_usage() / 1024 / 1024, 3, '.', '');
		$maxMem = (new Bytes(ini_get('memory_limit')))->getBytes();
		$memUsage = memory_get_peak_usage() / $maxMem * 100;
		$memBar = '<img src="' . ProgressBar::getBar($memUsage) . '" />';

		ob_start();
		require(__DIR__ . '/FloatTime.phtml');
		$content = ob_get_clean();

		if ($this->withCSS) {
			$content .= '<style>' . file_get_contents(
					dirname(__FILE__) . '/../CSS/TaylorProfiler.css'
				) . '</style>';
		}
		return $content;
	}

	/**
	 * @return int|number|string
	 * @throws DatabaseException
	 */
	private function getDBTime()
	{
		$dbTime = 0;
		$db = class_exists('Config')
			? Config::getInstance()->getDB() : null;
		$ql = $db ? $db->getQueryLog() : null;
		if ($ql && is_array($ql)) {
			$dbTime = ArrayPlus::create($ql)->column('sumtime')->sum();
			$dbTime = number_format($dbTime, 3, '.', '');
		} elseif ($ql && is_object($ql)) {
			$dbTime = $ql->getDBTime();
			$dbTime = number_format($dbTime, 3, '.', '');
		} elseif (isset($db->queryTime)) {
			$dbTime = $db->queryTime;
			$dbTime = number_format($dbTime, 3, '.', '');
		}
		//debug($dbTime, gettype2($db), $db->queryLog, $db->queryTime);
		return $dbTime;
	}

}
