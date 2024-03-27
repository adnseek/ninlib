<?php

/**
 * Class uTestBase
 * PHPUnit alternative
 */
class UTestBase extends AppControllerBE
{

	protected $start;

	/**
	 * true/false counter
	 * @var array
	 */
	protected $stat = [
		true => 0,
		false => 0,
	];

	public function render()
	{
//		$this->index->bodyClasses[] = 'fullScreen';
		$content = '<style>
	body, td {
		/*font-size: 9pt;*/
	}
</style>';
		$content .= '
		<table class="nospacing table">
		<tr>
			<!--th>File</th-->
			<th>Function</th>
			<th>Line</th>
			<th>Comment</th>
			<th>OK</th>
			<th>Get</th>
			<th>Must</th>
			<th>Elapsed</th>
		';
		$tests = get_class_methods($this);
		foreach ($tests as $function) {
			if (substr($function, 0, 5) == 'test_') {
				$this->start = microtime(true);
				ob_start();
				$contentPlus = call_user_func([$this, $function]);
				$debug = ob_get_clean();
				if ($debug) {
					$content .= '<tr><td colspan="99">' . $debug . '</td></tr>';
				}
				$content .= $contentPlus;
			}
		}
		$content .= '</table>';
		//$content .= getDebug($this->stat);
		$content = $this->encloseIn(new HtmlString('&mu;Test'), $content);
		if ($GLOBALS['profiler']) {
			$content .= $GLOBALS['profiler']->printTimers(1);
		}
		return $content;
	}

	public function assert($bool)
	{
		return $this->assertEqual($bool, true);
	}

	public function assertEqual($v1, $v2, $comment = '', $bool = null)
	{
		$row = [];
		$dbt = debug_backtrace();
		$i = 0;
		$db = $dbt[$i];
		$ignoreList = ['assert', 'assertEqual', 'assertNotEqual', 'assertTrue', 'assertGreaterThan'];
		while (in_array($db['function'], $ignoreList, true)) {
			$db = $dbt[++$i];
		}
		//Debug::peek($db);
		//$row['file'] = basename($db1['file']);
		$row['function'] = $db['function'];
		$file = ifsetor($db['file']) ? file($db['file']) : [];
		$line = ifsetor($db['line']);
		$row['line'] = ifsetor($file[$line]);

		$row['comment'] = $comment;

		$row['result'] = '';

		$row['v1'] = is_array($v1)
			? '<pre style="font-size: 8pt;">' . htmlspecialchars($this->get_var_dump($v1)) . '</pre>' : $v1 . '';
		$row['v2'] = is_array($v2)
			? '<pre style="font-size: 8pt;">' . htmlspecialchars($this->get_var_dump($v2)) . '</pre>' : $v2 . '';

		if (is_null($bool)) {
			$bool = $v1 == $v2;
		}
		if ($bool) {
			$row['result'] = '<font color="green">OK</font>';
		} else {
			$row['result'] = '<font color="red">FAILED</font>';
		}
		$this->stat[$bool]++;

		$row['dur'] = number_format(microtime(true) - $this->start, 3, '.', '');

		static $odd = 0;
		if (Request::isCLI()) {
			return $row;
		}

		return '<tr class="' . ($odd++ % 2 ? 'odd' : '') . '"><td>' . implode('</td><td>', $row) . '</td></tr>';
	}

	public function get_var_dump($a)
	{
		ob_start();
		var_dump($a);
		return ob_get_clean();
	}

	public function assertNotEqual($v1, $v2, $comment = null)
	{
		return $this->assertEqual($v1, $v2, $comment, $v1 !== $v2);
	}

	public function test_OK()
	{
		return $this->assertEqual(1, 1, '1=1?');
	}

	public function run()
	{
		$tests = get_class_methods($this);
		foreach ($tests as $function) {
			if (strpos($function, 'test_') === 0) {
				echo '>> ', $function, BR;
				$this->start = microtime(true);
				ob_start();
				$contentPlus = $this->$function();
				if ($contentPlus) {
					echo '<< ', strip_tags(implode(TAB, $contentPlus)), BR;
				}
				$debug = ob_get_clean();
				if ($debug) {
					$this->echoBQ($debug);
				}
				echo '<< ', number_format(microtime(true) - $this->start, 3, '.', ''), 'ms', BR;
			}
		}
	}

	public function echoBQ($text)
	{
		$lines = explode(PHP_EOL, $text);
		foreach ($lines as &$line) {
			$line = str_repeat(' ', 8) . $line;
			echo $line, BR;
		}
	}

	public function assertGreaterThan($must, $is)
	{
		return $this->assertTrue($is >= $must);
	}

	public function assertTrue($is)
	{
		return $this->assertEqual($is, $is);
	}

}
