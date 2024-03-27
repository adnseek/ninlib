<?php

class IndexBE extends IndexBase
{

	static public $isBE = true;

	public $projectName = 'nadlib|BE';

	public $template = 'template/template.phtml';

	/**
	 * @var Menu
	 */
	public $menu;

	/**
	 * @var AutoLoad
	 */
	protected $al;

	protected $nadlibFromDocRoot;

	protected $nadlibFromCWD;

	public function __construct()
	{
		//debug_pre_print_backtrace();
		if (!class_exists('Config')) {
			require_once 'ConfigBE.php';
			$this->config = ConfigBE::getInstance();
		}
		parent::__construct($this->config);

		$this->config->defaultController = HomeBE::class;
		$this->config->documentRoot = str_replace('/vendor/spidgorny/nadlib/be', '', $this->config->documentRoot);
		$this->config->documentRoot = str_replace('/nadlib/be', '', $this->config->documentRoot);
		//$config->documentRoot = $this->config->documentRoot ?: '/';	// must end without slash
		// it's not reading the config.json from /be/, but from the project root
		$this->config->config['View']['folder'] = '../be/template/';

		//$c->documentRoot = str_replace('/vendor/spidgorny/nadlib/be', '', $c->documentRoot);	// for CSS
		//Config::getInstance()->documentRoot .= '/vendor/spidgorny/nadlib/be';
		//base href will be fixed manually below

//		$this->config->appRoot = str_replace('/vendor/spidgorny/nadlib/be', '', $this->config->appRoot);
		//$this->config->appRoot = str_replace('/nadlib/be', '', $this->config->appRoot);

		$this->al = AutoLoad::getInstance();
		$this->nadlibFromDocRoot = $this->al->nadlibFromDocRoot . '../';
		$this->al->nadlibFromDocRoot = $this->nadlibFromDocRoot;
		$this->nadlibFromCWD = $this->al->nadlibFromCWD;

		//$this->al->componentsPath = new Path('components/');
//		$componentsURL = $this->al->componentsPath->getURL();
		$componentsURL = 'components/';
		//debug($this->al->componentsPath, $this->al->appRoot, $componentsURL);
		$this->header['modernizr.js'] = '<script src="' . $componentsURL . 'modernizr/modernizr.js"></script>';  // Must be header and not footer
		$this->addCSS($componentsURL . 'bootstrap/css/bootstrap.min.css');
		$this->addCSS($this->nadlibFromDocRoot . 'be/css/main.css');
		$this->addCSS($this->nadlibFromDocRoot . 'CSS/TaylorProfiler.less');
		$this->addJQuery();
		$this->addJS($componentsURL . 'bootstrap/js/bootstrap.js');
		$this->addJS($this->nadlibFromDocRoot . 'js/addTiming.js');

		$this->user = new BEUser();
		$this->user->id = 'nadlib';
		$this->user->try2login('admin');

		$this->ll = new LocalLangDummy();
		//debug($this->ll);

		$menuItems = $this->getMenu();
		$menu = $this->loadBEmenu($menuItems);
		$this->menu = new Menu($menuItems, 0);
		$this->menu->ulClass = 'nav navbar-nav';
		$this->menu->liClass = '';
		$this->menu->recursive = false;
		$this->menu->renderOnlyCurrent = true;
		$this->menu->useControllerSlug = false;
		$this->menu->setBasePath();  // because 1und1 rewrite is not enabled
		//debug($this->menu->basePath);
		$docRoot = $this->request->getDocumentRoot();
		$docRoot = new Path($docRoot);
		//$docRoot->trimIf('nadlib');
		//$nadlibPath = new Path($this->nadlibFromDocRoot);
		//$docRoot->append($nadlibPath);
		//$docRoot = str_replace(AutoLoad::getInstance()->nadlibFromDocRoot.'be', '', $docRoot);	// remove vendor/spidgorny/nadlib/be
		$docRoot->trimIf('be');
		//debug($this->request->getDocumentRoot(), $docRoot, $this->nadlibFromDocRoot, $nadlibPath);
		$this->menu->basePath->setPath($docRoot);
	}

	/**
	 * @return array
	 */
	public static function getMenu() {
		$menu = [
			'HomeBE'         => 'Home',
			'ServerStat'     => new Recursive('Info', [
				SysInfo::class  => 'Sys Info',
				'ServerStat'    => 'Server Stat',
				'ServerData'    => 'Server Data',
				'SessionView'   => 'Session',
				'Cookies'       => 'Cookies',
				'ConfigView'    => 'config.yaml',
				'PHPInfo'       => 'phpinfo()',
				'About'         => 'About',
				'Documentation' => 'Documentation',
				'IniCheck'      => 'php.ini Check',
				'TimeTrack'     => 'Time Track',
				'Issues'        => 'Issues',
			]),
			'UnitTestReport' => new Recursive('Test', [
				'UnitTestReport' => 'Unit Test Report',
				'ValidatorCheck' => 'Validator Check',
				'TestQueue'      => 'Test Queue',
			]),
			'ExplainQuery'   => new Recursive('DB', [
				'AlterDB'      => 'Alter DB',
				'AlterCharset' => 'Alter Charset',
				'AlterTable'   => 'Alter Table',
				'AlterIndex'   => 'Alter Indexes',
				'OptimizeDB'   => 'Optimize DB',
				'ExplainQuery' => 'Explain Query',
				'Localize'     => 'Localize',
			]),
			'ClearCache'     => new Recursive('FE', [
				'ClearCache'   => 'Clear Cache',
				'JumpFrontend' => '<- Frontend',
			]),
		];
		return $menu;
	}

	function loadBEmenu(array $menu) {
		if (class_exists('Spyc')) {
			if (file_exists('class/config.yaml')) {
				$c = Spyc::YAMLLoad('../../../../class/config.yaml');
				//debug($c['BEmenu']);
				if ($c['BEmenu']) {
					//$c['BEmenu'] = array('FE' => $c['BEmenu']);
					foreach ($c['BEmenu'] as $key => $sub) {
						if (is_array($sub)) {
							$menu['ClearCache']->elements[$key] = new Recursive($key, $sub);
						} else {
							$menu['ClearCache']->elements[$key] = $sub;
						}
					}
				}
			}
		}

		return $menu;
	}

	public function renderController()
	{
		$c = get_class($this->controller);
		/** @var $c Controller */
		//$public = $c::$public;	// Parse error:  syntax error, unexpected T_PAAMAYIM_NEKUDOTAYIM
		$vars = get_class_vars($c);
		$public = $vars['public'];
		if ($public || $this->user->isAuth()) {
			$this->controller->user = $this->user;  // BEUser instead of grUser
			$content = parent::renderController();
		} else {
			$this->error('Accessing this page requires a valid login');
			$loginForm = new LoginForm();
			$loginForm->withRegister = false;
			$content = $loginForm->layout->wrap(
				$this->content .
				$loginForm->render()
			);
			$this->content->clear();
			/*throw new LoginException('
				Login first <a href="vendor/spidgorny/nadlib/be/">here</a>');
			*/
		}
		return $content;
	}

	public function renderTemplate($content)
	{
		$v = new View($this->template, $this);
		$v->content = $this->content . $content;
		$v->title = $this->controller
			? strip_tags($this->controller->title)
			: null;
		$v->sidebar = $this->showSidebar();
		$v->version = @file_get_contents('VERSION');
		$lf = new LoginForm('inlineForm');  // too specific - in subclass
		$v->loginForm = $lf->dispatchAjax();
		// is the root of the project
		$v->baseHref = $this->request->getLocation();
		//$v->baseHref = str_replace('/vendor/spidgorny/nadlib/be', '', $v->baseHref);	// for CSS
		$content = $v->render();  // not concatenate but replace
		return $content;
	}

	public function showSidebar()
	{
		$m = new Menu($this->menu->items->getData(), 1);
		$m->recursive = false;
		$m->renderOnlyCurrent = true;
		$m->useControllerSlug = false;
		$m->useRecursiveURL = false;
		$m->setCurrent(1);
		//$m->useRecursiveURL = false;
		//$m->setBasePath();	// because 1und1 rewrite is not enabled
		//$docRoot = $m->request->getDocumentRoot();
		//$docRoot = str_replace(AutoLoad::getInstance()->nadlibFromDocRoot.'be', '', $docRoot);	// remove vendor/spidgorny/nadlib/be
		//$m->basePath->setPath($docRoot.$this->nadlibFromDocRoot.'be/');
		//debug($m);
		return '<div class="_well" style="padding: 0;">' . $m . '</div>' .
			parent::showSidebar();
	}

}
