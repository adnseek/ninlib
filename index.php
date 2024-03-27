<?php

if (!function_exists('__')) {
	function __($a, $sub1 = null, $sub2 = null, $sub3 = null)
	{
		$a = str_replace('%1', $sub1, $a);
		$a = str_replace('%2', $sub2, $a);
		$a = str_replace('%3', $sub3, $a);
		return $a;
	}
}

/** Should not be called $i because Index::getInstance() will return $GLOBALS['i'] */
//require_once __DIR__ . '/Controller/NadlibIndex.php';
//$i2 = new NadlibIndex();
//echo $i2->render();
