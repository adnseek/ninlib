<?php

class ViewPHP7
{

	/**
	 * PHP 5.6
	 * @param array ...$variables
	 */
	public function set(...$variables)
	{
		// returns just ['variables']
		$ReflectionMethod = new ReflectionMethod(__CLASS__, __FUNCTION__);
		$params = $ReflectionMethod->getParameters();
		$paramNames = array_map(function ($item) {
			/** @var $item ReflectionParameter */
			return $item->getName();
		}, $params);

		$bt = debug_backtrace();
		$caller = $bt[0];
//		debug($caller);
		$file = $caller['file'];
		$fileLines = file($file);
		$line = $fileLines[$caller['line'] - 1];
		preg_match('#\((.*?)\)#', $line, $match);
		$varList = $match[1];
		$varList = str_replace('$', '', $varList);
		$paramNames = trimExplode(',', $varList);
//		debug($line, $paramNames);
		$variables = array_combine($paramNames, $variables);
		foreach ($variables as $key => $val) {
			$this->$key = $val;
		}
	}

}
