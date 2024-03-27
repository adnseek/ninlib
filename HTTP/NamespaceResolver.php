<?php

class NamespaceResolver implements ResolverInterface
{

	/**
	 * @var Request
	 */
	public $request;
	protected $ns;

	public function __construct(array $tryNS = [])
	{
		$this->request = Request::getInstance();
		$this->ns = $tryNS;
	}

	public function getController($returnDefault = true)
	{
		$levels = $this->request->getURLLevels();
		if ($levels) {
			$levels = array_reverse($levels);
			$last = null;
			foreach ($levels as $class) {
				$last = $this->tryNamespaces($class);
				if ($last) {
					break;
				}
			}
			if ($last) {
				$controller = $last;
			} else {
				$controller = $this->getDefault($returnDefault);
			}
		} else {
			$controller = $this->getDefault($returnDefault);
		}
		return $controller;
	}

	public function tryNamespaces($class)
	{
		$last = null;
		foreach ($this->ns as $prefix) {
			$classWithNS = $prefix . $class;
			if (class_exists($classWithNS)) {
				$last = $classWithNS;
				break;
			}
		}
		return $last;
	}

	public function getDefault($returnDefault)
	{
		if ($returnDefault && class_exists('Config')) {
			// not good as we never get 404
			return Config::getInstance()->defaultController;
		}

		return null;
	}

}
