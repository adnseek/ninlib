<?php /** @noinspection ForgottenDebugOutputInspection */

require_once __DIR__ . DIRECTORY_SEPARATOR . '../static.php';
/**
 * May already be defined in TYPO3
 */
if (!function_exists('debug')) {

	/**
	 * @param mixed ...$params
	 */
	function debug(...$params)
	{
		$params = func_num_args() === 1 ? $params[0] : func_get_args();
		if (class_exists(Debug::class)) {
			$debug = Debug::getInstance();
			$debug->debug($params);
		} elseif (DEVELOPMENT) {
			ob_start();
			var_dump($params);
			$dump = ob_get_clean();
			$dump = str_replace("=>\n", ' =>', $dump);
			if (!function_exists('xdebug_break')) {
				$dump = htmlspecialchars($dump);
			}
			echo '<pre>' . $dump . '</pre>';
			debug_pre_print_backtrace();
		}
	}
}

if (!function_exists('debugList')) {

	function debugList(array $a, $name = null)
	{
		$debug = Debug::getInstance();
		$debug->name = $name;
		foreach ($a as &$b) {
			$b = $b . '';
		}
		debug($a);
	}

	function debugTable(array $a)
	{
		debug(new slTable($a));
	}

	function ddie()
	{
		debug(func_get_args());
		die(__FUNCTION__ . '#' . __LINE__);
	}
}

if (!function_exists('d')) {

	function d(...$a)
	{
		$params = func_num_args() === 1 ? $a[0] : $a;
		if (DEVELOPMENT) {
			ob_start();
			var_dump($params);
			$dump = ob_get_clean();
			$dump = str_replace("=>\n", ' =>', $dump);
			if (!function_exists('xdebug_break')) {
				$dump = htmlspecialchars($dump);
			}
			echo '<pre>' . $dump . '</pre>';
		}
	}

	/**
	 * @param ...$ignored
	 */
	function nodebug(...$ignored)
	{
	}

	function getDebug(...$params)
	{
		$debug = Debug::getInstance();
		$dh = new DebugHTML($debug);
		$content = $dh->printStyles();
		if (ifsetor($params[1]) === DebugHTML::LEVELS) {
			$levels = ifsetor($params[2]);
			$params[1] = $levels;
		}
		$content .= $dh::view_array(...$params);
		return $content;
	}

	/**
	 * @param ..$a
	 * @noinspection ForgottenDebugOutputInspection
	 */
	function pre_print_r(...$a)
	{
		if (PHP_SAPI !== 'cli') {
			echo '<pre class="pre_print_r" style="white-space: pre-wrap;">';
			/** @noinspection ForgottenDebugOutputInspection */
			print_r(count($a) === 1 ? $a[0] : $a);
			echo '</pre>';
		} else {
			/** @noinspection ForgottenDebugOutputInspection */
			print_r(sizeof($a) === 1 ? $a[0] : $a);
			echo PHP_EOL;
		}
	}

	function get_print_r($a)
	{
		return '<pre class="pre_print_r" style="white-space: pre-wrap;">' .
			print_r($a, true) .
			'</pre>';
	}

	/** @noinspection ForgottenDebugOutputInspection */
	function pre_var_dump(...$a)
	{
		echo '<pre class="pre_var_dump" style="white-space: pre-wrap; font-size: 8pt;">';
		/** @noinspection ForgottenDebugOutputInspection */
		var_dump(count($a) === 1 ? $a[0] : $a);
		echo '</pre>';
	}

	function debug_once()
	{
		static $used = null;
		if (is_null($used)) {
			$used = [];
		}
		$trace = debug_backtrace();
		array_shift($trace); // debug_once itself
		$first = array_shift($trace);
		$key = $first['file'] . '.' . $first['line'];
		if (!ifsetor($used[$key])) {
			$v = func_get_args();
			//$v[] = $key;
			debug(...$v);
			$used[$key] = true;
		}
	}

	function debug_size($a)
	{
		if (is_object($a)) {
			$vals = get_object_vars($a);
			$keys = array_keys($vals);
		} else {
			$vals = $a;
			$keys = array_keys($a);
		}
		$assoc = [];
		foreach ($keys as $key) {
			$sxe = $vals[$key];
			if ($sxe instanceof SimpleXMLElement) {
				$sxe = $sxe->asXML();
			}
			//$len = strlen(serialize($vals[$key]));
			$len = strlen(json_encode($sxe));
			//$len = gettype($vals[$key]) . ' '.get_class($vals[$key]);
			$assoc[$key] = $len;
		}
		debug($assoc);
	}

	function debug_get_backtrace()
	{
		ob_start();
		if (phpversion() >= '5.3.6') {
			debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		} else {
			debug_print_backtrace();
		}
		$content = ob_get_clean();
		$content = str_replace(dirname(getcwd()), '', $content);
		$content = str_replace('C:\\Users\\' . getenv('USERNAME') . '\\AppData\\Roaming\\Composer\\vendor\\phpunit\\phpunit\\src\\', '', $content);
		return $content;
	}

	function debug_pre_print_backtrace()
	{
		if (DEVELOPMENT) {
			require_once __DIR__ . '/../HTTP/Request.php';
			if (!Request::isCLI()) {
				print '<pre style="
				white-space: pre-wrap;
				background: #eeeeee;
				border-radius: 5px;
				padding: 0.5em;
				">';
			}
			ob_start();
			if (PHP_VERSION >= '5.3.6') {
				/** @noinspection ForgottenDebugOutputInspection */
				debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			} else {
				/** @noinspection ForgottenDebugOutputInspection */
				debug_print_backtrace();
			}
			$content = ob_get_clean();
			$content = str_replace(dirname(getcwd()), '', $content);
			$search = 'C:\\Users\\' . getenv('USERNAME') .
				'\\AppData\\Roaming\\Composer\\vendor\\phpunit\\phpunit\\src\\';
			$content = str_replace($search, '', $content);
			echo $content;
			if (!Request::isCLI()) {
				print '</pre>';
			}
		}
	}

	/**
	 * http://php.net/manual/en/function.error-reporting.php#65884
	 * @param int $value
	 * @return string
	 */
	function error2string($value)
	{
		$level_names = [
			E_ERROR => 'E_ERROR',
			E_WARNING => 'E_WARNING',
			E_PARSE => 'E_PARSE',
			E_NOTICE => 'E_NOTICE',
			E_CORE_ERROR => 'E_CORE_ERROR',
			E_CORE_WARNING => 'E_CORE_WARNING',
			E_COMPILE_ERROR => 'E_COMPILE_ERROR',
			E_COMPILE_WARNING => 'E_COMPILE_WARNING',
			E_USER_ERROR => 'E_USER_ERROR',
			E_USER_WARNING => 'E_USER_WARNING',
			E_USER_NOTICE => 'E_USER_NOTICE'];
		if (defined('E_STRICT')) {
			$level_names[E_STRICT] = 'E_STRICT';
		}
		$levels = [];
		if (($value & E_ALL) === E_ALL) {
			$levels[] = 'E_ALL';
			$value &= ~E_ALL;
		}
		foreach ($level_names as $level => $name) {
			if (($value & $level) === $level) {
				$levels[] = $name;
			}
		}
		return implode(' | ', $levels);
	}

	/**
	 * similar to gettype() but return more information depending on data type in HTML
	 * @param mixed $something
	 * @param bool $withHash
	 * @param null $isCLI
	 * @return HTMLTag|HtmlString
	 */
	function typ($something, $withHash = true, $isCLI = null)
	{
		if ($isCLI === null) {
			$isCLI = Request::isCLI();
		}
		$type = gettype($something);
		if ($type === 'object') {
			if ($withHash) {
				$hash = md5(spl_object_hash($something));
				$hash = substr($hash, 0, 6);
				require_once __DIR__ . '/../HTTP/Request.php';
				if (!Request::isCLI()) {
					require_once __DIR__ . '/../Value/Color.php';
					$color = new Color('#' . $hash);
					$complement = $color->getComplement();
					if (!$isCLI) {
						$hash = new HTMLTag('span', [
							'class' => 'tag',
							'style' => 'background: ' . $color . '; color: ' . $complement,
						], $hash);
					}
				}
				$typeName = get_class($something) . '#' . $hash;
			} else {
				$typeName = get_class($something);
			}
		} else {
			$typeName = $type;
		}

		$bulma = [
			'string' => 'is-primary',
			'NULL' => 'is-danger',
			'object' => 'is-warning',
			'array' => 'is-link',
			'boolean' => 'is-info',
			'integer' => 'is-success',
			'resource' => '',
		];
		$class = ifsetor($bulma[$type]) . ' tag';

		if ($type === 'string') {
			$typeName .= '[' . strlen($something) . ']';
		}
		if ($type === 'array') {
			$typeName .= '[' . count($something) . ']';
		}

		if (!Request::isCLI()) {
			return new HTMLTag('span', ['class' => $class], $typeName, true);
		}
		return new HtmlString($typeName);
	}

	/**
	 * @param array|mixed $something
	 * @return array|HtmlString
	 */
	function gettypes($something)
	{
		if (is_array($something)) {
			$types = [];
			foreach ($something as $key => $element) {
				$types[$key] = strip_tags(typ($element));
			}
			return $types;
		}

		return typ($something);
		//return json_encode($types, JSON_PRETTY_PRINT);
	}
}

if (!function_exists('invariant')) {
	function invariant($test, $format_str = null, ...$args)
	{
		if ($test) {
			return;
		}
		if ($format_str instanceof Exception) {
			throw $format_str;
		}
		throw new RuntimeException($format_str ?? 'Invariant failed', ...$args);
	}
}

if (!function_exists('llog')) {
	function llog(...$vars)
	{
		$caller = Debug::getCaller();
		$jsonOptions = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

		if (defined('JSON_UNESCAPED_LINE_TERMINATORS')) {
			$jsonOptions |= JSON_UNESCAPED_LINE_TERMINATORS;
		}

		$vars = array_map(static function ($el) {
			if (is_object($el)) {
				if (!($el instanceof stdClass)) {
					if (method_exists($el, '__toString')) {
						return $el->__toString();
					}
					return typ($el, true, true);
					// or trim(strip_tags(typ($el)));
				}
			}
			return $el;
		}, $vars);

		$type = null;
		if (count($vars) === 1) {
			$type = '[' . gettype(first($vars)) . ']';
			$output = json_encode(first($vars), JSON_THROW_ON_ERROR | $jsonOptions);
		} else {
			$type = '';
			$output = json_encode($vars, JSON_THROW_ON_ERROR | $jsonOptions);
		}
		if (strlen($output) > 80) {
			$output = json_encode(count($vars) === 1 ? first($vars) : $vars, JSON_THROW_ON_ERROR | $jsonOptions | JSON_PRETTY_PRINT);
		}
		/** @noinspection ForgottenDebugOutputInspection */
		$runtime = number_format(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3);
		error_log("[${runtime}] {$caller} {$type} {$output}");
	}
}
