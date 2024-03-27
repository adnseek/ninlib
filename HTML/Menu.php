<?php

use spidgorny\nadlib\HTTP\URL;

/**
 * Doesn't extend Controller as it makes an infinite loop as a menu is made in Controller::__construct
 */
class Menu /*extends Controller*/
{

	/**
	 * Public for access rights. Will convert to ArrayPlus automatically
	 * @var ArrayPlus
	 */
	public $items = [
		'default' => 'Default Menu Item',
	];
	/**
	 * Set to not NULL to see only specific level
	 * @var int|null
	 */
	public $level = null;

	/**
	 * Set to call getMenuSuffix() on each object in menu
	 * @var bool
	 */
	public $tryMenuSuffix = false;

	/**
	 * It used to be just the "Controller" but menu needs the full path
	 * @var string in a format "TopPage/SubPage/Controller"
	 */
	public $current;

	/**
	 * @var UserModelInterface
	 */
	protected $user;

	/**
	 * Will display only the first level of the menu
	 * @var bool
	 */
	public $renderOnlyCurrent = true;

	public $menuTag = 'ul';

	public $ulClass = 'nav nav-list nav-pills nav-stacked menu csc-menu list-group';

	public $itemTag = 'li';

	/**
	 * list-group-item for bootstrap 1/2
	 * @var string
	 */
	public $liClass = '';

	public $normalAClass = '';

	public $activeAClass = 'act';

	/**
	 * @var URL
	 */
	public $basePath;

	public $recursive = true;

	/**
	 * @var bool - will control the URL generation as only last path element or '/'-separated path
	 * This is required for $useControllerSlug to work
	 */
	public $useRecursiveURL = true;

	/**
	 * @var bool
	 * TRUE: explode('/', $path)
	 * FALSE: Request::getControllerSting()
	 * Call setBasePath() after changing this to make an effect
	 */
	public $useControllerSlug = true;

	public $controllerVarName = 'c';

	/**
	 * @var Request
	 */
	public $request;

	public $forceRootPath;

	public $useDropDown = true;

	public function __construct(array $items, $level = null, UserModelInterface $user = null)
	{
		//parent::__construct();
		$this->items = new ArrayPlus($items);
		$this->level = $level;
		$this->request = Request::getInstance();
		//$this->tryInstance();
		$this->user = $user;
		$this->useControllerSlug = $this->request->apacheModuleRewrite();
		$this->setBasePath();
		$this->setCurrent($level);
	}

	/**
	 * Called by the constructor
	 * @param $level
	 */
	public function setCurrent($level)
	{
		$level = (int)$level;
//		$appRootPath = $this->request->getPathAfterAppRoot();
		$appRootPath = $this->basePath->getPath();
		$rootPath = $appRootPath->getLevels();
//		debug($rootPath, $level);

		if ($this->useRouter()) {
			$this->current = ifsetor($rootPath[$level], $this->request->getControllerString());
		} elseif ($this->useControllerSlug) {
			if ($rootPath) {
				$this->current = implode('/', $rootPath);
			} else {
				$this->current = $this->request->getControllerString();
			}
		} else {
			$this->current = $this->request->getControllerString();
		}
		00 && debug([
			'cwd' => getcwd(),
			'docRoot' => $this->request->getDocumentRoot() . '',
			'getPathAfterDocRoot' => $this->request->getPathAfterDocRoot() . '',
			'useRouter' => $this->useRouter(),
			'useControllerSlug' => $this->useControllerSlug,
			'rootPath' => $rootPath,
			'getControllerString' => $this->request->getControllerString(),
			'level' => $level,
			'current' => $this->current
		]);
	}

	public function setControllerVarName($c)
	{
		$this->controllerVarName = $c;
		$this->setBasePath();
	}

	/**
	 * Called by the constructor
	 */
	public function setBasePath()
	{
		if ($this->useRouter()) {
			$this->setBasePathFromRouter();
		} elseif ($this->useControllerSlug) {
			$this->setBasePathFromSlug();
		} else {
			$this->setBasePathFromClass();
		}
	}

	public function debug()
	{
		return [
			'class_exists(Config)' => class_exists('Config'),
//			'Config::getInstance()->config[Controller]' =>
// 				(class_exists('Config') && isset($config->config['Controller']))
//				? $config->config['Controller']
//				: null,
			'useRouter' => $this->useRouter(),
			'useControllerSlug' => $this->useControllerSlug,
			'documentRoot' => $this->basePath->documentRoot,
			'appRoot' => AutoLoad::getInstance()->getAppRoot() . '',
			'nadlibRoot' => AutoLoad::getInstance()->nadlibRoot,
			'nadlibRootFromDocRoot' => AutoLoad::getInstance()->nadlibFromDocRoot,
			'current' => $this->current,
			'basePath' => $this->basePath . '',
			'cwd' => getcwd(),
			'docRoot' => $this->request->getDocumentRoot() . '',
			'getPathAfterDocRoot' => $this->request->getPathAfterDocRoot() . '',
			'useRouter()' => $this->useRouter(),
			'rootPath' => $this->basePath->getPath()->getLevels(),
			'getControllerString' => $this->request->getControllerString(),
			'level' => $this->level,
			'levels' => $this->basePath->getPath()->getLevels(),
		];
	}

	/**
	 * not finished
	 */
	public function setBasePathFromRouter()
	{
		$path = new URL();
		$path->clearParams();
		$this->basePath = $path;
	}

	public function setBasePathFromSlug()
	{
		$path = new URL();
		$autoLoad = AutoLoad::getInstance();
		$appRoot = $autoLoad->getAppRoot();
		if (basename($appRoot) === 'be') {
			$docRoot = $_SERVER['DOCUMENT_ROOT'] . $path->documentRoot;
			//$commonRoot = URL::getCommonRoot($docRoot, $appRoot);
			$path->setPath(cap($path->documentRoot . '/' . URL::getRelativePath($docRoot, $appRoot)));
			$path->setParams();
		} else {
			//debug($path->documentRoot);
			$path->setPath(cap($path->documentRoot));
			$path->setParams();
		}
		// commented when using the slug
		//$path->setParam($this->controllerVarName, '');	// forces a link with "?c="
		$this->basePath = $path;
	}

	public function setBasePathFromClass()
	{
		$path = new URL();
		$path->clearParams();
		if ($this->controllerVarName) {
			$path->setParam($this->controllerVarName, '');    // forces a link with "?c="
		}
		$this->basePath = $path;
	}

	/**
	 * Used by AccMailer
	 */
	public function filterACL()
	{
		foreach ($this->items as $class => &$item) {
			if (!$this->user->can($class, '__construct')) {
				unset($this->items[$class]);
			}
		}
	}

	public function getRootpath()
	{
		if ($this->forceRootPath !== null) {
			return $this->forceRootPath;
		}

		if ($this->useRecursiveURL) {
			$rootPath = $this->request->getURLLevels();
			$rootPath = array_slice($rootPath, 0, $this->level); // avoid searching for sub-menu of Dashboard/About
			if (!$rootPath) { // no rewrite, then find the menu with current as a key
				if (ifsetor($this->items[$this->current])) {
					// if $current is a top-level menu then add it, otherwise search (see below)

					if ($this->level > 0) {
						$rootPath = [
							$this->current,   // commented otherwise it will show a corresponding submenu
						];
					}


				}
			}
			//debug($rootpath, sizeof($rootpath), $this->level, $this->current);
			if (count($rootPath) < $this->level) { // URL contains only the sub-page without the path, search for it
				$found = $this->items->find($this->current);
				if ($found) {
//					debug($found, $this->current); exit;
					if (!in_array($this->current, $found)) {
						//$found[] = $this->current;
					}
					$rootPath = $found;
					$this->current = implode('/', $found);
				}
				//debug($rootpath);
			}
			if ($this->level === 0) {
				// $this->current = $this->current; // no change
			} elseif (ifsetor($this->items[$this->current]) instanceof Recursive) {
				$this->current = $this->current . '/' . $this->current;
			}
		} else {
			$controller = $this->request->getControllerString();
			if (ifsetor($this->items[$controller])) {
				$rootPath = [$controller];
			} else {    // search inside
				$rootPath = $this->items->find($controller);
				$rootPath = [first($rootPath)];    // needed for getItemsOnLevel
			}
		}
		return $rootPath;
	}

	public function render()
	{
		$content = '';

//		llog('level', $this->level);
		if (is_null($this->level)) {
			$items = $this->items instanceof ArrayPlus ? $this->items->getData() : $this->items;
//			llog(count($items));
			$content .= $this->renderLevel($items, [], 0);
			return $content;
		}

		$rootPath = $this->getRootpath();
//		llog('rootPath', $rootPath);
		$itemsOnLevel = $this->getItemsOnLevel($rootPath);
		if ($this->level === 1) {
			nodebug([
				'current' => $this->current,
				'sizeof($rootPath)' => count($rootPath),
				'level' => $this->level,
				'rootPath' => $rootPath,
				'itemsOnLevel' => $itemsOnLevel,
			]);
//		llog('itemsOnLevel', $this->level, count($itemsOnLevel));
			$content .= $this->renderLevel($itemsOnLevel, $rootPath, $this->level);
		} else {
			$items = $this->items instanceof ArrayPlus ? $this->items->getData() : $this->items;
			$content .= $this->renderLevel($items, array(), 0);
		}
		return $content;
	}

	/**
	 * Will retrieve the sub-elements on the specified path
	 * @param array $rootPath
	 * @return array
	 */
	protected function getItemsOnLevel(array $rootPath)
	{
		$fullRecursive = new Recursive(null, $this->items->getData());
		$sub = $fullRecursive->findPath($rootPath);
		if ($sub instanceof Recursive) {
			$items = $sub->getChildren();
		} else {
			$items = [];
		}

		if ($this->tryMenuSuffix) {
			foreach ($items as $class => &$name) {
				try {
					//$o = new $class();							// BAD instantiating
					//if (method_exists($o, 'getMenuSuffix')) {
					$methods = get_class_methods($class);
					//if ($class == 'AssignHardware') debug($class, $methods, in_array('getMenuSuffix', $methods));
					if ($methods && in_array('getMenuSuffix', $methods, true)) {
						$o = new $class();
						$o->postInit();
						$name .= $o->getMenuSuffix();
					}
				} catch (AccessDeniedException $e) {
					unset($items[$class]);
				}
			}
		}

		if (-1 == $this->level) {
			debug([
				'level' => $this->level,
				'rootpath' => $rootPath,
				'sub' => $sub,
				'items' => $items
			]);
		}
		return $items;
	}

	/**
	 * @param array $items
	 * @param array $root
	 * @param $level
	 * @param null $ulClass
	 * @return string
	 */
	public function renderLevelItems(array $items, array $root = [], $level = 0, $ulClass = null)
	{
		$content = '';
		foreach ($items as $class => $name) {
			if ($name) {    // empty menu items indicate menu location for a controller
				$path = $this->getClassPath($class, $root);
				//$renderOnlyCurrentSubmenu = $this->renderOnlyCurrent ? $class == $this->current : true;
				$renderOnlyCurrentSubMenu = !$this->renderOnlyCurrent ||
					in_array($class, trimExplode('/', $this->current));
				$hasChildren = $renderOnlyCurrentSubMenu
					&& $name instanceof Recursive
					&& $name->getChildren();
				$cur = $this->isCurrent($class, $root, $level);
				$activeLIclass = $this->liClass . ($cur ? ' active' : '');
				$activeAclass = $cur ? $this->activeAClass : $this->normalAClass;
				if ($name instanceof HTMLTag) {
					$aTag = $name . '';
				} else {
					if ($this->useDropDown && $hasChildren) {
						$activeLIclass .= ' dropdown';
						$activeAclass .= ' dropdown-toggle';
						$aTag = '<a href="' . $path . '" class="' . $activeAclass . '" data-toggle="dropdown">' . __($name . '') . ' <b class="caret"></b></a>' . "\n";
					} else {
						$aTag = '<a href="' . $path . '" class="' . $activeAclass . '">' . __($name . '') . '</a>' . "\n";
					}
				}
				nodebug([
					'class' => $class,
					'$this->renderOnlyCurrent' => $this->renderOnlyCurrent,
//					'getURLLevels()' => $this->request->getURLLevels(),
					'$this->current' => $this->current,
					'$renderOnlyCurrentSubMenu' => $renderOnlyCurrentSubMenu,
					'$this->recursive' => $this->recursive,
					'hasChildren' => $hasChildren]);
				if ($this->recursive && $hasChildren) {
					$root_class = array_merge($root, [$class]);
					/** @var Recursive $subItem */
					$subItem = $items[$class];
					$contentSubMenu = $this->renderLevel(
						$subItem->getChildren(),
						$root_class,
						$level + 1,
						'dropdown-menu'
					);
				} else {
					$contentSubMenu = '';
				}
				if ($this->itemTag) {
					$content .= new HTMLTag($this->itemTag, [
							'class' => $activeLIclass,
						], $aTag . $contentSubMenu, true) . "\n";
				} else {
					$content .= $aTag . $contentSubMenu;
				}
			}
		}
		return $content;
	}

	public function renderLevel(array $items, array $root = [], $level = 0, $ulClass = null)
	{
		$content = $this->renderLevelItems($items, $root, $level, $ulClass);
		//debug($this->current);
		$content = '<' . $this->menuTag .
			' class="' . ($ulClass ?: $this->ulClass) . '">' . PHP_EOL .
			$content . '</' . $this->menuTag . '>';
		return $content;
	}

	/**
	 * For http://appdev.nintendo.de/~depidsvy/posaCards/ListSales/ChartSales/BreakdownTotal/?filter[id_country]=2
	 * to work we need to split by '/' not only the path but also parameters
	 * @param string $class
	 * @param array $subMenu
	 * @param int $level
	 * @return bool
	 */
	public function isCurrent($class, array $subMenu = [], $level = 0)
	{
		$ret = false;
		$combined = null;
		if (ifsetor($class) && $class[0] === '?') {    // hack begins
			$parts = trimExplode('/', $_SERVER['REQUEST_URI']);
			//debug($parts, $class);
			if (end($parts) === $class) {
				$ret = true;
			} else {
				$ret = null;
			}
		} elseif ($subMenu) {
			$combined = implode('/', $subMenu) . '/' . $class;
			$ret = ($this->current == $class)
				|| ($combined == $this->current);
			if ($level > 0 && !$ret) {
				$ret = ($subMenu[($level - 1)] == $this->current && $class == $this->current);
			}
		} elseif (contains($class, '/')) {
			$classWithoutSlash = trimExplode('/', $class);
			$classWithoutSlash = $classWithoutSlash[0] ?? '';
			$ret = $this->current == $classWithoutSlash;
		} else {
			$ret = $this->current == $class;
		}
		//if ($this->level === 0) {
		nodebug([
			'class' => $class,
			'class[0]' => ifsetor($class) ? $class[0] : null,
			'subMenu' => $subMenu,
			'combined' => $combined,
			'current' => $this->current,
			'contains /' => contains($class, '/'),
			'ret' => $ret,
		]);
		//}
		return $ret;
	}

	/**
	 * Finds the path to the menu item inside the menu tree
	 * @param $class
	 * @param array $root
	 * @return string
	 */
	public function getClassPath($class, array $root)
	{
		// http://someshit
		if (str_startsWith($class, 'http')) {
			return $class;
		}

		// Controller?param=x
		if (str_contains($class, '?')) {
			return $class;
		}

		if ($this->useRecursiveURL) {
			$path = array_merge($root, [$class]);
		} else {
			$path = [$class];
		}

		if ($path && $this->useControllerSlug) {
			if ($this->useRecursiveURL) {
				$link = cap($this->basePath) . implode('/', $path);
			} else {
				$link = $this->basePath;
				$link->replaceController($path);
			}
		} else {
			if (ifsetor($class) && $class[0] === '#') {
				$link = $this->basePath->setFragment($class);
			} else {
				$link = $this->basePath->setParam($this->controllerVarName, $class);
			}
		}
//		llog([
//			'class' => $class,
//			'root' => $root,
//			'path' => $path,
//			'useRecursiveURL' => $this->useRecursiveURL,
//			'useControllerSlug' => $this->useControllerSlug,
//			'basePath' => $this->basePath . '',
//			'link' => $link . ''
//		]);
		return $link;
	}

	public function __toString()
	{
		return $this->render() . '';
	}

	/**
	 * ACL. Constructs each menu object and reacts on access denied exception
	 */
	public function tryInstance()
	{
		foreach ($this->items as $class => $_) {
			try {
				new $class();
			} catch (Exception $e) {
				unset($this->items[$class]);
			}
		}
	}

	public function renderBreadcrumbs()
	{
		$ul = new UL($this->items->getData());
		$ul->links = $this->items->getKeys()->getData();
		$ul->links = array_combine($ul->links, $ul->links);
		$ul->linkWrap = '<a href="###LINK###">|</a>';
		$ul->before = '<ol class="breadcrumb">';
		$ul->after = '</ol>';
		if ($ul->links) {
			return $ul;
		} else {
			return null;
		}
	}

	/**
	 * @return null
	 */
	public function useRouter()
	{
		if (class_exists('Config')) {
			$config = Config::getInstance();
			$useRouter = isset($config->config['Controller'])
				? ifsetor($config->config['Controller']['useRouter'])
				: ($this->request->apacheModuleRewrite() && class_exists('Router'));
		} else {
			$useRouter = $this->useRecursiveURL;
		}
		return $useRouter;
	}

}
