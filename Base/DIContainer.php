<?php

/**
 * Class DIContainer
 * http://fabien.potencier.org/article/17/on-php-5-3-lambda-functions-and-closures
 */
class DIContainer
{

	protected $values = [];

	public function __set($id, $value)
	{
		//echo __METHOD__, ' ('.$id.')', BR;
		$this->values[$id] = $value;
	}

	public function __get($id)
	{
		//echo __METHOD__, ' ('.$id.')', BR;
		// we check for is_null() because sometimes ['user'] is not logged-in
		if (!isset($this->values[$id]) && !is_null($this->values[$id])) {
			debug(array_keys($this->values));
			throw new InvalidArgumentException(sprintf(
				__METHOD__ . ': value "%s" is not defined.', $id));
		}
		$v = $this->values[$id];
		/*		print_r(array(
					'id' => $id,
					'type' => gettype($v),
					'callable' => is_callable($v),
					'object' => is_object($v),
				)); echo BR;*/

		return is_callable($v) //&& is_object($v)
			? $this->values[$id] = $v($this)
			: $v;
	}

	/*	function asShared($callable) {
			return function ($c) use ($callable) {
				static $object;

				if (is_null($object)) {
					$object = $callable($c);
				}

				return $object;
			};
		}
	*/
}
