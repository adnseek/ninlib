<?php

class PathResolver implements ResolverInterface
{

	/**
	 * @var Request
	 */
	public $request;

	public function __construct()
	{
		$this->request = Request::getInstance();
	}

	public function getController($returnDefault = true)
	{
		$levels = $this->request->getURLLevels();
//		debug($levels);
		if ($levels) {
			$levels = array_reverse($levels);
			$last = null;
			foreach ($levels as $class) {
				// RewriteRule should not contain "?c="
				nodebug(
					$class,
					class_exists($class . 'Controller'),
					class_exists($class)
				);
				// to simplify URL it first searches for the corresponding controller
				if ($class && class_exists($class . 'Controller')) {    // this is untested
					$last = $class . 'Controller';
					break;
				}
				if (class_exists($class)) {
					$last = $class;
					break;
				}
			}    // foreach
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

	public function getDefault($returnDefault)
	{
		if ($returnDefault && class_exists('Config')) {
			// not good as we never get 404
			$controller = Config::getInstance()->defaultController;
			// remove namespaces
			$controller = last(trimExplode('\\', $controller));
		} else {
			$controller = null;
		}
		return $controller;
	}

}
