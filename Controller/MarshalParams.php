<?php

/**
 * Class MarshalParams
 * Poor programmers' dependency injection solution
 * Will check the reflection of the class to see which parameters need to be injected
 */
class MarshalParams
{

	/**
	 * @var object
	 * This is an object with functions which require DI or
	 * Container should have functions starting with 'get' and the class name
	 */
	public $container;

	/**
	 * @var Request
	 */
	public $request;

	public function __construct($object)
	{
		$this->container = $object;
		$this->request = Request::getInstance();
	}

	/**
	 * @param string $class
	 * @return object
	 * @throws ReflectionException
	 */
	public function make($class)
	{
		return $this->makeInstanceWithInjection($class);
	}

	/**
	 * @param string $class
	 * @param object $container
	 * @return object
	 * @throws ReflectionException
	 */
	public function makeInstanceWithInjection($class)
	{
		$cr = new ReflectionClass($class);
		$constructor = $cr->getConstructor();
		if ($constructor) {
			$init = $this->getFunctionArguments($constructor);
//			debug($class, $constructor->getName(), $init);
			// PHP 7
			//$instance = new $class(...$init);
			$reflector = new ReflectionClass($class);
			$instance = $reflector->newInstanceArgs($init);
		} else {
			$instance = new $class();
		}
		return $instance;
	}

	/**
	 * @param object $container
	 * @param ReflectionMethod $constructor
	 * @return array
	 * @throws ReflectionException
	 */
	public function getFunctionArguments(ReflectionMethod $constructor)
	{
		$container = $this->container;
		$init = []; // parameter values to the constructor
		$params = $constructor->getParameters();
		foreach ($params as $param) {
			$name = $param->getName();
			if ($param->isArray() || $param->isDefaultValueAvailable()) {
				$init[$name] = $param->getDefaultValue();
			} else {
				if (method_exists($param, 'getType')) {
					$type = $param->getType();
				} else {
					$type = $param->getClass()->name;
				}
				if ($type) {
					$init[$name] = $this->getParameterValue($param, $type);
				} else {
					$init[$name] = null;
				}
			}
		}
		return $init;
	}

	public function getParameterValue($param, $type)
	{
		$container = $this->container;
		$typeClass = method_exists($type, 'getName')
			? $type->getName()
			: $type . '';
		if (!is_string($type) && $type->isBuiltin()) {
			$value = $param->getDefaultValue();
		} elseif (is_object($container)) {
			$typeGenerator = 'get' . $typeClass;
//						debug($typeClass, get_class($container), $typeGenerator);
			// does not work with namespaces
			// e.g. Config->getSymfony\\Contracts\\Cache\\CacheInterface
			//llog($param->getName(), $typeGenerator);
			if (method_exists($container, $typeGenerator)) {
				$value = $container->$typeGenerator();
			} else {
				// build the dependency
				$value = $this->makeInstanceWithInjection($typeClass);
			}
		} elseif (is_array($container)) {
			$injector = ifsetor($container[$typeClass]);
			if (is_callable($injector)) {
				$value = $injector();
			} else {
				$value = $injector;
			}
		}
		return $value;
	}

	/**
	 * @param string $method
	 * @return mixed
	 * @throws ReflectionException
	 */
	public function call($method)
	{
		return $this->callMethodByReflection($this->container, $method);
	}

	/**
	 * Will detect parameter types and call getInstance() or new $class
	 * @param object $proxy
	 * @param string $method
	 * @return mixed
	 * @throws ReflectionException
	 */
	private function callMethodByReflection($proxy, $method)
	{
		$r = new ReflectionMethod($proxy, $method);
		if ($r->getNumberOfParameters()) {
			$assoc = [];
			foreach ($r->getParameters() as $param) {
				$name = $param->getName();
				if ($this->request->is_set($name)) {
					$assoc[$name] = $this->getParameterByReflection($param);
				} elseif ($param->isDefaultValueAvailable()) {
					$assoc[$name] = $param->getDefaultValue();
				} else {
					$assoc[$name] = null;
				}
			}
			//debug($assoc);
			$content = call_user_func_array([$proxy, $method], $assoc);
		} else {
			$content = $proxy->$method();
		}
		return $content;
	}

	public function getParameterByReflection(ReflectionParameter $param)
	{
		$name = $param->getName();
		if ($param->isArray()) {
			$return = $this->request->getArray($name);
		} else {
			$return = $this->request->getTrim($name);
			$paramClassRef = $param->getClass();
			//debug($param->getPosition(), $paramClassRef, $paramClassRef->getName());
			if ($paramClassRef && class_exists($paramClassRef->getName())) {
				$paramClass = $paramClassRef->getName();
//				debug($param->getPosition(), $paramClass,
//				method_exists($paramClass, 'getInstance'));
				if (method_exists($paramClass, 'getInstance')) {
					$obj = $paramClass::getInstance($return);
					$return = $obj;
				} else {
					$obj = new $paramClass(/*$assoc[$name]*/);
					$return = $obj;
				}
			}
		}
		return $return;
	}

}
