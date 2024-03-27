<?php

if (!function_exists('get_overriden_methods')) {

	/**
	 * http://www.php.net/manual/en/function.get-class-methods.php
	 * @param string $class
	 * @return array|null
	 * @throws ReflectionException
	 */
	function get_overriden_methods($class)
	{
		$rClass = new ReflectionClass($class);
		$array = null;

		foreach ($rClass->getMethods() as $rMethod) {
			try {
				// attempt to find method in parent class
				new ReflectionMethod($rClass->getParentClass()->getName(),
					$rMethod->getName());
				// check whether method is explicitly defined in this class
				if ($rMethod->getDeclaringClass()->getName() == $rClass->getName()
				) {
					// if so, then it is overriden, so add to array
					$array[] = $rMethod->getName();
				}
			} catch (Exception $e) { /* was not in parent class! */
			}
		}

		return $array;
	}

}
