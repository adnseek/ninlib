<?php

namespace nadlib;

/**
 * Interface IndexInterface
 * @package nadlib
 */
interface IndexInterface
{

	public static function getInstance($createNew = false);

	public function render();

	public function addJS($source);

	public function message($text);

	public function error($text);

	public function success($text);

}
