<?php
/**
 * @author Jan "johno" Suchal <johno@jsmf.net>
 * @see http://johno.jsmf.net/knowhow/mixins/mixins.phps
 **/

class Mixer
{

	public $methodPerformers = [];

	public function __construct()
	{
		$this->methodPerformers = [];
	}

	public function __call($method, $args)
	{
		if (array_key_exists($method, $this->methodPerformers)) {
			$performer = $this->methodPerformers[$method];
			$this->copyInternals($this, $performer);
			$result = call_user_func_array([$performer, $method], $args);
			$this->copyInternals($performer, $this);
			return $result;
		} else {
			throw new BadMethodCallException();
		}
	}

	private function copyInternals($from, $to)
	{
		$properties = get_object_vars($from);
		$target = new ReflectionClass(get_class($to));
		foreach ($properties as $name => $value) {
			if (!$target->hasProperty($name)
				|| !$target->getProperty($name)->isPrivate()) {
				$to->$name = $value;
			}
		}
	}

	public function inject($behaviour)
	{
		$methods = get_class_methods(get_class($behaviour));
		foreach ($methods as $method) {
			$this->methodPerformers[$method] = $behaviour;
		}
	}

}
