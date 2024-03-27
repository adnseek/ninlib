<?php

class ClosureCache
{

	static public $closures;

	/**
	 * @var callable
	 */
	public $function;

	public $result;

	protected function __construct(callable $function)
	{
		$this->function = $function;
	}

	public function get()
	{
		if (!$this->result) {
			$this->result = call_user_func($this->function);
		}
		return $this->result;
	}

	public function __toString()
	{
		return $this->get() . '';
	}

	public function __invoke()
	{
		return $this->get();
	}

	public static function getInstance($key, callable $function)
	{
		$hash = md5(json_encode($key));
		if (isset(self::$closures[$hash])) {
			return self::$closures[$hash];
		} else {
			$new = new self($function);
			self::$closures[$hash] = $new;
			return $new;
		}
	}

}
