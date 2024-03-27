<?php

class LogEntry
{

	public $time;

	public $action;

	public $data;

	public static $log2file;

	public static function initLogging()
	{
		self::$log2file = DEVELOPMENT;
	}

	public function __construct($action, $data)
	{
		$this->time = microtime(true);
		$this->action = $action;
		$this->data = $data;
		if (self::$log2file) {
			$sData = $this->shorten($data);
			$r = Request::getInstance();
			//$ip = $r->getClientIP();	// this is very expensive
			$ip = $r->getBrowserIP();
			error_log($ip . ' ' . $action . ' ' . $sData);
		}
	}

	public function __toString()
	{
		$floating = substr($this->time - floor($this->time), 2);    // cut 0 from 0.1
		$floating = substr($floating, 0, 4);
		$sData = $this->shorten($this->data);
		$paddedAction = $this->action;
		if (strlen($paddedAction) < 20) {
			$paddedAction = str_pad($paddedAction, 20, ' ', STR_PAD_RIGHT);
		}
		return implode("\t", [
			date('H:i:s', $this->time) . '.' . $floating,
			$paddedAction,
			$this->data ? $sData : null
		]);
	}

	/**
	 * Render function for multiple log entries
	 * @param array $log
	 * @return array
	 */
	public static function getLogFrom(array $log): array
	{
		return [
			'<pre class="debug" style="font-family: monospace; white-space: pre-wrap;">',
			implode(PHP_EOL, $log),
			'</pre>',
		];
	}

	/**
	 * @param mixed $data
	 * @return bool|float|int|string
	 */
	public function shorten($data)
	{
		if (is_string($data) || is_int($data)) {
			$sData = $data;
		} else {
			$jsonOptions = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
			$sData = json_encode($data, $jsonOptions);
		}

		if (contains($sData, '<')) {
			$sData = htmlspecialchars($sData);    // no tags
		} elseif (contains($sData, "\n")) {
			$sData = '<pre>' . substr($sData, 0, 1000) . '</pre>';
		} else {
			$sData = substr($sData, 0, 1000);
		}
		return $sData;
	}

}
