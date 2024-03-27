<?php

class MiniIndex extends AppControllerBE
{

	/**
	 * @var MiniIndex
	 */
	protected static $instance;
	/**
	 * @var Menu
	 */
	public $menu;
	/**
	 * @var AppControllerBE
	 */
	public $controller;
	public $header = [];
	public $footer = [];

	public $layout;

	/**
	 * @var Config
	 */
	public $config;

	public function __construct()
	{
		parent::__construct();
		$this->config = Config::getInstance();
	}

	/**
	 * Set $createAllowed to true only in index.php when making the original Index
	 * all other situation will return NULL if it's not instantiated yet
	 * thus avoiding infinite loops.
	 * @param bool $createAllowed
	 * @return MiniIndex
	 */
	public static function getInstance($createAllowed = true)
	{
		$self = get_called_class();
		if (!self::$instance) {
			if ($createAllowed) {
				self::$instance = new $self();
				/** @var self::$instance MiniIndex */
				self::$instance->init();
			}
		}
		return self::$instance;
	}

	public function init()
	{
		$this->controller = $this->request->getController();
		//debug(get_class($this), spl_object_hash($this));
		//debug(get_class($this->controller), spl_object_hash($this->controller));
		if (method_exists($this->controller, 'postInit')) {
			$this->controller->postInit();
		}
	}

	public function render()
	{
		if ($this->controller->layout === 'none' || $this->request->isAjax()) {
			$content = $this->renderController();
		} else {
			$v = new View('template.phtml', $this);
			$v->content = $this->renderController();
			$v->sidebar = $this->showSidebar();
			$v->baseHref = $this->request->getLocation();
			$this->title = $this->controller->title;    // after $controller->render() before $view->render()
			$content = $v->render();
		}
		return $content;
	}

	public function renderController()
	{
		$content = '';
		if ($this->controller) {
			try {
				$content = $this->controller->render();
			} catch (Exception $e) {
				$content = $this->error($e->getMessage());
			}
			if (!$this->request->isAjax() && $this->controller->layout instanceof Wrap) {
				$content = $this->controller->layout->wrap($content);
			}
		}
		return $content;
	}

	public function error($text)
	{
		return '<div class="ui-state-error alert alert-error alert-danger padding">' . $text . '</div>';
	}

	public function showSidebar()
	{
		if (method_exists($this->controller, 'sidebar')) {
			return $this->controller->sidebar();
		}
	}

	public function message($text)
	{
		return '<div class="message">' . $text . '</div>';
	}

	public function addJQueryUI()
	{
		$this->addJQuery();
		$this->footer['jqueryui.js'] = '<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8.23/jquery-ui.min.js"></script>';
		$this->addCSS('http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.23/themes/base/jquery-ui.css');
	}

	public function addJQuery()
	{
		$this->footer['jquery.js'] = '
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js"></script>
		<script>window.jQuery || document.write(\'<script src="vendor/jquery/jquery.min.js"><\/script>\')</script>
		';
	}

	public function addCSS($source)
	{
		$this->header[$source] = '<link rel="stylesheet" type="text/css" href="' . $source . '" />';
	}

public function addJS($source)
	{
		$this->footer[$source] = '<script src="' . $source . '"></script>';
	}

	public function renderProfiler()
	{
		$content = '';
		$profiler = $GLOBALS['profiler'];
		/** @var $profiler TaylorProfiler */
		if ($profiler) {
			$content = $profiler->renderFloat();
			$content .= $profiler->printTimers(true);
		} elseif (DEVELOPMENT) {
			$content = TaylorProfiler::renderFloat();
		}
		return $content;
	}

}
