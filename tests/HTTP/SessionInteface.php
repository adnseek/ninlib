<?php

interface SessionInterface
{

	function get($key);

	function save($key, $val);

	function has($key);

	function getAll();

	function delete($key);

}
