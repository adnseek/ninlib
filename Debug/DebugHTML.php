<?php

class DebugHTML
{

	const LEVELS = 'LEVELS';

	public static $stylesPrinted = false;
	public static $defaultLevels = 4;
	/**
	 * @var Debug
	 */
	public $helper;

	protected $htmlPrologSent = false;

	public function __construct(Debug $helper)
	{
		$this->helper = $helper;
	}

	private static function shortenType(string $type)
	{
		$type = str_replace('string', 'S', $type);
		$type = str_replace('array', 'A', $type);
		$type = str_replace('integer', 'I', $type);
		$type = str_replace('boolean', 'B', $type);
		return $type;
	}

	public function render()
	{
		$levels = self::$defaultLevels;
		$args = func_get_args();
		if (is_array($args)) {
			$levels = $this->getLevels($args) ?: self::$defaultLevels;
			//$args['levels'] = $levels;
		}

		$db = debug_backtrace();
		$db = array_slice($db, 3, sizeof($db));

		$content = static::renderHTMLView($db, $args, $levels);
		$content .= static::printStyles();
		if (!headers_sent()) {
			if (method_exists($this->helper->index, 'renderHead')) {
				$this->helper->index->renderHead();
			} elseif (!headers_sent() && !$this->htmlPrologSent) {
				$content = '<!DOCTYPE html>
				<html lang="en-US">
				<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
				' . $content;
				$this->htmlPrologSent = true;
			}
		}
		return $content;
	}

	public function getLevels(array &$args)
	{
		if (sizeof($args) === 1) {
			$a = $args[0];
			$levels = self::$defaultLevels;
		} else {
			$a = $args;
			if ($a[1] === self::LEVELS) {
				$levels = intval($a[2]);
				$a = $a[0];
			} else {
				$levels = self::$defaultLevels;
			}
		}
		$args = $a;
		return $levels;
	}

	public function renderHTMLView(array $db, $a, $levels)
	{
		$props = $this->getProps($db, $a);

		$backlog = Debug::getBackLog(1, 6);
		$trace = Debug::getTraceTable($db);
//		$trace = '<ul><li>' . Debug::getBackLog(20, 6, '<li>') . '</ul>';

		$content = '
			<div class="debug">
				<div class="caption">
					' . $this->renderProps($props) . '
					<a href="javascript: void(0);" onclick="
						var a = this.nextSibling.nextSibling;
						a.style.display = a.style.display == \'block\' ? \'none\' : \'block\';
					">' . $backlog . '</a>
					<div style="display: none;">' . $trace . '</div>
				</div>
				<div class="">
					' . static::view_array($a, $levels) . '
				</div>
			</div>';
		return $content;
	}

	protected function getProps($db, $a)
	{
		static $lastElapsed;

		$first = ifsetor($db[2]);
		if ($first) {
			$function = $this->helper->getMethod($first);
		} else {
			$function = '';
		}

		$next = ifsetor($db[1]);
		$file = ifsetor($next['file']);
		$file = basename(dirname($file)) . '/' . basename($file);
		$file .= '#' . ifsetor($next['line']);

		$props = [];
		if ($this->helper->name) {
			$props += [
				'Name:' => $this->helper->name,
			];
		}
		$props += [
//			'Class:' => ifsetor($first['class']),
//			'Function:' => ifsetor($first['function']),
//			'File:' => $file,
			'Type:' => typ($a),
		];
		if (!is_array($a) && !is_object($a) && !is_resource($a)) {
			$props['Length:'] = strlen($a);
		}

		require_once __DIR__ . '/TaylorProfiler.php';
		$memPercent = TaylorProfiler::getMemUsage() * 100;
		require_once __DIR__ . '/../HTML/ProgressBar.php';
		$pb = new ProgressBar();
		$pb->destruct100 = false;
		$props['Mem:'] = number_format(memory_get_usage(true) / 1024 / 1024, 3, '.', '') . 'M ' .
			$pb->getImage($memPercent) . ' of ' . ini_get('memory_limit');

		$memDiff = TaylorProfiler::getMemDiff();
		$memDiff = $memDiff[0] == '+'
			? '<span style="color: green">' . $memDiff . '</span>'
			: '<span style="color: red">' . $memDiff . '</span>';
		$props['Mem ±:'] = $memDiff;

		$elapsed = number_format(microtime(true) - $_SERVER['REQUEST_TIME'], 3);
		$elapsedDiff = '+' . number_format($elapsed - $lastElapsed, 3, '.', '');
		$props['Elapsed:'] = $elapsed . ' (<span style="color: green">' . $elapsedDiff . '</span>)' . BR;
		$lastElapsed = $elapsed;
		return $props;
	}

	public function renderProps(array $props)
	{
		$rows = [];
		foreach ($props as $key => $val) {
			$rows[] = '<span class="debug_prop">' . $key . '</span> ' . $val;
		}
		return implode(BR, $rows);
	}

	/**
	 * @param mixed $a
	 * @param int $levels
	 * @return string|NULL    - will be recursive while levels is more than zero, but NULL is a special case
	 */
	public static function view_array($a, $levels = 1, $tableClass = 'view_array font-mono')
	{
		if (is_object($a)) {
			if (method_exists($a, 'debug')) {
				$a = $a->debug();
				//} elseif (method_exists($a, '__toString')) {    // commenting this often leads to out of memory
				//	$a = $a->__toString();
				//} elseif (method_exists($a, 'getName')) {
				//	$a = $a->getName();	-- not enough info
			} elseif (method_exists($a, '__debugInfo')) {
				$a = $a->__debugInfo();
			} elseif ($a instanceof HtmlString) {
				$a = $a; // will take care below
			} elseif ($a instanceof SimpleXMLElement) {
				$a = 'XML[' . $a->asXML() . ']';
			} else {
				$a = get_object_vars($a);
			}
		}

		if (is_array($a)) {    // not else if so it also works for objects
			$content = '<table class="' . $tableClass . '">';
			foreach ($a as $i => $r) {
				$type = typ($r);
				$type = self::shortenType($type);
				$content .= '<tr>
					<td>' . htmlspecialchars($i) . '</td>
					<td>' . $type . '</td>
					<td>';

				//var_dump($levels); echo '<br/>'."\n";
				//echo '"', $levels, '": null: '.is_null($levels), ' ', gettype($r), BR;
				//debug_pre_print_backtrace(); flush();
				if (($a !== $r) && (is_null($levels) || $levels > 0)) {
					$content .= self::view_array($r,
						is_null($levels) ? null : $levels - 1, $tableClass);
				} else {
					$content .= '<i>Too deep, $level: ' . $levels . '</i>';
				}
				//$content = print_r($r, true);
				$content .= '</td></tr>';
			}
			$content .= '</table>';
		} elseif (is_object($a)) {
			if ($a instanceof HtmlString) {
				$content = 'html[' . $a . ']';
			} else {
				$content = '<pre style="font-size: 12px; white-space: pre-wrap">' .
					htmlspecialchars(print_r($a, true)) . '</pre>';
			}
		} elseif (is_resource($a)) {
			$content = $a;
		} elseif (is_string($a) && strstr($a, "\n")) {
			$content = '<pre style="font-size: 12px; white-space: pre-wrap">' .
				htmlspecialchars($a) . '</pre>';
		} elseif ($a instanceof __PHP_Incomplete_Class) {
			$content = '__PHP_Incomplete_Class';
		} elseif (is_string($a) && strlen($a) && $a[0] === '{') {
			$try = @json_decode($a);
			if ($try) {
				$content = '<pre style="white-space: pre-wrap">' . json_encode($try, JSON_PRETTY_PRINT) . '</pre>';
			} else {
				$content = htmlspecialchars($a . '');
			}
		} else {
			$content = htmlspecialchars($a . '');
		}
		return $content;
	}

	public static function printStyles()
	{
		if (Request::isCLI()) return '';
		$content = '';
		if (!self::$stylesPrinted) {
			$content = '<style>' . file_get_contents(__DIR__ . '/Debug.css') . '</style>';
			self::$stylesPrinted = true;
		} else {
			$content .= '<!-- styles printed -->';
		}
		return $content;
	}

}
