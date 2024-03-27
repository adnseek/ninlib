<?php

class DebugCLI
{

	/**
	 * @var Debug
	 */
	public $helper;

	public $name;

	public function __construct(Debug $helper)
	{
		$this->helper = $helper;
	}

	public static function canCLI()
	{
		$isCURL = ifsetor($_SERVER['HTTP_USER_AGENT']) && str_contains(ifsetor($_SERVER['HTTP_USER_AGENT'], ''), 'curl');
		return Request::isCLI() || $isCURL;
	}

	public function debug($args)
	{
		if (!DEVELOPMENT) {
			return;
		}
		$db = debug_backtrace();
		$db = array_slice($db, 2, sizeof($db));
		$trace = [];
		$row = first($db);
		$trace[] = $row['file'] . ':' . $row['line'];
		foreach ($db as $i => $row) {
			$trace[] = ' * ' . Debug::getMethod($row, ifsetor($db[$i + 1], []));
			if (++$i > 7) {
				break;
			}
		}
		echo '⎯⎯⎯⎯⎯⎯⎯⎯⎯ ¯\_(ツ)_/¯ ' . $this->helper->name . BR .
			implode(BR, $trace) . "\n";

		if ($args instanceof HtmlString) {
			$args = strip_tags($args);
		}

		if (is_object($args)) {
			echo 'Object: ', get_class($args), BR;
			if (method_exists($args, '__debugInfo')) {
				$args = $args->__debugInfo();
			} else {
				$args = get_object_vars($args);   // prevent private vars
			}
		}

		ob_start();
		var_dump($args);
		$dump = ob_get_clean();
		$dump = str_replace("=>\n", ' =>', $dump);
		echo $dump;
		echo str_repeat('⎯', 24), BR;
		$this->name = null;
	}

}
