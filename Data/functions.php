<?php

if (!function_exists('ae_arr')) {
	// This function returns random array element
	function ae_arr(&$arr)
	{
		return $arr[rand(0, sizeof($arr) - 1)];
	}
}
