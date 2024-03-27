<?php

class MockRequest
{

	public $log = [];

	public $pathAfterAppRootByPath;

	/**
	 * @var Request
	 */
	public $subject;

	public function __construct()
	{
		$this->subject = Request::getInstance();
	}

	public function __call($function, array $args)
	{
		$this->log[] = (object)[
			'function' => $function,
			'args' => $args,
		];
		return call_user_func_array([$this->subject, $function], $args);
	}

	public function getPathAfterAppRootByPath()
	{
		return $this->pathAfterAppRootByPath;
	}

	public function getURLLevels()
	{
		$levels = [];
		$path = $this->getPathAfterAppRootByPath();
		if (strlen($path) > 1) {    // "/"
			$levels = trimExplode('/', $path);
		}
		return $levels;
	}

}
