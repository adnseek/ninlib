<?php

if (!function_exists('first')) {

	/**
	 * Complements the built-in end() function
	 * @param array $list
	 * @return array|mixed
	 */
	function first(array $list)
	{
		reset($list);
		return current($list);
	}

	if (!function_exists('last')) {
		/**
		 * Complements the built-in end() function
		 * @param array $list
		 * @return array|mixed
		 */
		function last(array $list)
		{
			return end($list);
		}
	}

	/**
	 * This is equal to return next(each($list))
	 *
	 * @param array $list
	 * @return mixed
	 */
	function eachv(array &$list)
	{
		$current = current($list);
		next($list);
		return $current;
	}

	/**
	 * Array_combine only works when both arrays are indexed by numbers
	 * @used FullGrid
	 * @param array $a
	 * @param array $b
	 * @return array
	 */
	function array_combine_stringkey(array $a, array $b)
	{
		$ret = [];
		reset($b);
		foreach ($a as $key) {
			$ret[$key] = current($b);
			next($b);
		}
		return $ret;
	}

	/**
	 * http://stackoverflow.com/questions/173400/php-arrays-a-good-way-to-check-if-an-array-is-associative-or-sequential
	 * @param array $arr
	 * @return bool
	 */
	function is_assoc($arr)
	{
		return array_keys($arr) !== range(0, count($arr) - 1);
	}

	/**
	 * Makes it unique on a first level only
	 * http://php.net/manual/en/function.array-unique.php#116302
	 * @param array $matriz
	 * @return array
	 */
	function unique_multidim_array(array $matriz)
	{
		$aux_ini = [];
		foreach ($matriz as $n => $source) {
			$aux_ini[$n] = serialize($source);
		}

		$mat = array_unique($aux_ini);

		$entrega = [];
		foreach ($mat as $n => $serial) {
			$entrega[$n] = unserialize($serial);

		}
		return $entrega;
	}

	function unique_multidim_array_thru(array $matriz)
	{
		foreach ($matriz as $n => &$source) {
			if (is_array($source)) {
				$source = unique_multidim_array_thru($source);
			}
		}

		$matriz = unique_multidim_array($matriz);
		return $matriz;
	}

	/**
	 * Will call __toString() on every object in array
	 * @param array $a
	 * @return array
	 */
	function array_to_string(array $a)
	{
		foreach ($a as &$val) {
			if (is_array($val)) {
				$val = array_to_string($val);
			} else {
				$val = $val . '';
			}
		}
		return $a;
	}

	function without(array $source, $remove)
	{
		if (phpversion() > 5.6) {
			return array_filter($source, function ($el, $key) use ($remove) {
				if (is_array($remove)) {
					return !in_array($key, $remove);
				}

				return $key != $remove;
			}, ARRAY_FILTER_USE_BOTH);
		}

		return array_diff_key($source, array_flip((array)$remove));
	}

	/**
	 * @param $callback - return both keys and values
	 * @param array $array
	 * @return array|false
	 */
	function array_map_keys($callback, array $array)
	{
		$keys = array_keys($array);
		$temp = array_map($callback, $keys, $array);    // return ['key', 'value']
		$keys = array_column($temp, 0);
		$values = array_column($temp, 1);
		$result = array_combine($keys, $values);
		return $result;
	}

	function array_widths(array $arr)
	{
		$widths = [];
		foreach ($arr as $key => $row) {
			$widths[$key] = sizeof($row);
		}
		return $widths;
	}

	function recursive_array_diff($a1, $a2)
	{
		$r = [];
		foreach ($a1 as $k => $v) {
			if (array_key_exists($k, $a2)) {
				if (is_array($v)) {
					$rad = recursive_array_diff($v, $a2[$k]);
					if (count($rad)) {
						$r[$k] = $rad;
					}
				} else {
					if ($v != $a2[$k]) {
						$r[$k] = $v;
					}
				}
			} else {
				$r[$k] = $v;
			}
		}
		return $r;
	}

}

/**
 * https://stackoverflow.com/questions/4790453/php-recursive-array-to-object
 * Convert an array into a stdClass()
 *
 * @param array $array The array we want to convert
 *
 * @return  object
 */
function arrayToObject($array)
{
	// First we convert the array to a json string
	$json = json_encode($array);

	// The we convert the json string to a stdClass()
	$object = json_decode($json);

	return $object;
}


/**
 * Convert a object to an array
 *
 * @param object $object The object we want to convert
 *
 * @return  array
 */
function objectToArray($object)
{
	// First we convert the object into a json string
	$json = json_encode($object);

	// Then we convert the json string to an array
	return json_decode($json, true);
}

// https://www.reddit.com/r/PHPhelp/comments/7987wv/is_there_a_php_equivalent_of_javascripts_arrayfind/
function array_find(callable $callback, array $array) {
	foreach ($array as $key => $value) {
		if ($callback($value, $key, $array)) {
			return $value;
		}
	}
}
