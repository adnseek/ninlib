<?php

class AppControllerBE extends Controller
{

/**
	 * Protect from unauthorized access
	 * @var bool
	 */
	public static $public = false;

	/**
	 * -forceDL in CLI will re-download and extract data
	 * @var bool
	 */
	public $forceCronjobDL = false;
	/**
	 * - force in CLI will force process data even if they were processed recently
	 * @var bool
	 */
	public $forceCronjob = false;
	/**
	 * @var string
	 */
	public $nadlibFromDocRoot;

	public $layout = '<div class="col-md-9">|</div>';

	public function __construct()
	{
		parent::__construct();
		if (!static::$public) {
			if (!$this->user) {
				throw new AccessDeniedException(
					__('Access denied to page %1. No user.',
						get_class($this)));
			}
			if (!$this->user->isAdmin()) {
				throw new AccessDeniedException(__('Access denied to page %1. User is not admin.', get_class($this)));
			}
		}
		if (class_exists('Index')) {
			$this->index = Index::getInstance();
		}
		//debug($this->request->getAll());
		if ($this->request->getBool('forceDL')) {
			$this->forceCronjobDL = true;
		}
		if ($this->request->getBool('force')) {
			$this->forceCronjob = true;
		}
		$this->nadlibFromDocRoot = AutoLoad::getInstance()->nadlibFromDocRoot;
		$this->layout = new Wrap($this->layout);
	}

	public function log($class, ...$message)
	{
		//echo $class, ' ', print_r($message, true), BR;
		Debug::getInstance()->consoleLog([
			'class' => $class,
			'message' => $message
		]);
	}

	public function makeURL(array $params = [], $prefix = '?')
	{
		$url = parent::makeURL($params, $this->nadlibFromDocRoot . 'be/?');
		return $url;
	}

}
