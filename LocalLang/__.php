<?php

if (!function_exists('__')) {    // conflict with cakePHP

	function __($code, $r1 = null, $r2 = null, $r3 = null)
	{
		if (class_exists('Config')) {
			$config = Config::getInstance();
		} else {
			$config = null;
		}
		nodebug($code, !!$config,
			is_object($config) ? get_class($config) : gettype($config),
			!!$config->getLL());
		if (!empty($config) && $config->getLL()) {
			$text = $config->getLL()->T($code, $r1, $r2, $r3);
			//echo '<pre>', get_class($index->ll), "\t", $code, "\t", $text, '</pre><br />', "\n";
			return $text;
		} else {
			$code = LocalLang::Tp($code, $r1, $r2, $r3);
			return $code;
		}
	}

	/**
	 * Same as __(), but calls only str_replace() without translating
	 * @param $code
	 * @param mixed $r1
	 * @param mixed $r2
	 * @param mixed $r3
	 * @return string
	 */
	function __p($code, $r1 = null, $r2 = null, $r3 = null)
	{
		$index = null;
		if (class_exists('Config')) {
			$index = Config::getInstance();
		}
		//debug(!!$index, get_class($index), !!$index->ll, get_class($index->ll));
		if ($index && $index->getLL()) {
			$text = $index->getLL()->Tp($code, $r1, $r2, $r3);
			//echo '<pre>', get_class($index->ll), "\t", $code, "\t", $text, '</pre><br />', "\n";
			return $text;
		} else {
			return $code;
		}
	}

}
