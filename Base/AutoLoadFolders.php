<?php

class AutoLoadFolders
{

	/**
	 * @var array
	 */
	public $folders = [];

	/**
	 * If set $this->log will collect output here
	 * @var null|array
	 */
	public $collectDebug = null;

	public $debug = false;

	public $al;

	/**
	 * @var Debug
	 */
	protected $debugger;

	/**
	 * @var bool
	 */
	protected $saveFolders = true;

	public function __construct(AutoLoad $al)
	{
		$this->al = $al;
		require_once __DIR__ . '/../Debug/Debug.php';
		//$this->debugger = Debug::getInstance();
		//if (isset($_SESSION[__CLASS__])) unset($_SESSION[__CLASS__]);
		$this->folders = $this->getFoldersFromSession();
		if ($this->folders) {
			$this->al->stat['folders'] = 'fromSession';
		} else {
			$this->al->stat['folders'] = 'fromConfig';
			$this->folders[null] = $this->getFolders();
		}
		$this->folders = unique_multidim_array($this->folders);
		if (0) {
			pre_print_r([
				$this->folders, $this->al->stat
			]);
		}
	}

	public function getFoldersFromSession()
	{
		return [];
	}

	public function getFolders()
	{
		TaylorProfiler::start(__METHOD__);
		require_once __DIR__ . '/../HTTP/Request.php';

		$this->getFoldersFromConfig();
		$folders = (array)ifsetor($this->folders['']);    // modified by the line above
		//pre_print_r($this->folders);
		//$this->al->stat['folders'] .= ', '.sizeof($plus);
		//$folders = array_merge($folders, $plus);		// should come first to override /be/

		$plus = $this->getFoldersFromConfigBase();
		$this->al->stat['folders'] .= ', ' . sizeof($plus);
		$folders = array_merge($folders, $plus);
		//debug($folders);
		//debug($this->classFileMap, $_SESSION[__CLASS__]);

		TaylorProfiler::stop(__METHOD__);
		return $folders;
	}

	/**
	 * Will not return a list like before
	 * but will actively add the folders listed
	 * @return array
	 */
	public function getFoldersFromConfig()
	{
		TaylorProfiler::start(__METHOD__);
		$folders = [];
		$this->loadConfig();    // make sure (again)
		if (class_exists('Config') && Config::$includeFolders) {
			$folders = Config::$includeFolders;
			// append $this->appRoot before each
			foreach ($folders as &$el) {
				$this->addFolder($el);
			}
			if ($this->debug) {
//				pre_print_r($folders, $this->folders);
				echo __METHOD__ . ': Added folders', BR;
				pre_print_r($folders);
			}
		} else {
			// that's ok. relax. be quiet.
			//echo 'Config not found'.BR;
		}
		TaylorProfiler::stop(__METHOD__);
		return $folders;
	}

	public function loadConfig()
	{
		if ($this->debug) {
			debug_pre_print_backtrace();
			pre_print_r([
				'SCRIPT_FILENAME' => dirname($_SERVER['SCRIPT_FILENAME']),
				'getcwd' => getcwd(),
				'exists(cwd)' => file_exists(getcwd()),
				'appRoot' => $this->al->getAppRoot() . '',
				'exists(appRoot)' => file_exists($this->al->getAppRoot()),
				'exists(appRoot.class)' => file_exists($this->al->getAppRoot() . 'class'),
			]);
		}
		if (!class_exists('ConfigBase')) {
			require_once __DIR__ . '/ConfigBase.php';
		}
		if (!class_exists('Config', false)) {
			if ($this->debug) {
				echo __METHOD__ . ': Config class is found', BR;
			}
			//$configPath = dirname(URL::getScriptWithPath()).'/class/class.Config.php';
			$configPath1 = $this->al->getAppRoot() . 'class' . DIRECTORY_SEPARATOR . 'class.Config.php';
			$configPath2 = $this->al->getAppRoot() . 'class' . DIRECTORY_SEPARATOR . 'Config.php';
			$this->al->stat['configPath'] = $configPath1;
//			pre_print_r($configPath1, file_exists($configPath1));
//			pre_print_r($configPath2, file_exists($configPath2));
//			exit();
			if (file_exists($configPath1)) {
				/** @noinspection PhpIncludeInspection */
				include_once $configPath1;
				if ($this->debug) {
					echo __METHOD__ . ': Config in ' . $configPath1, BR;
				}
			} elseif (file_exists($configPath2)) {
				/** @noinspection PhpIncludeInspection */
				include_once $configPath2;
				//print('<div class="message">'.$configPath.' FOUND.</div>'.BR);
				if ($this->debug) {
					echo __METHOD__ . ': Config in ' . $configPath2, BR;
				}
			} else {
				// some projects don't need Config
				//print('<div class="error">'.$configPath.' not found.</div>'.BR);
				if ($this->debug) {
					echo __METHOD__ . ': Config class is found but file is unknown ', BR;
					debug($configPath1, $configPath2);
				}
			}
		} else {
			if ($this->debug) {
				echo __METHOD__ . ': Config class is found', BR;
				$rc = new ReflectionClass('Config');
				echo __METHOD__ . ': ' . $rc->getFileName(), BR;
			}
		}
	}

	public function getFoldersFromConfigBase()
	{
		TaylorProfiler::start(__METHOD__);
		require_once __DIR__ . '/ConfigBase.php';
		$folders = ConfigBase::$includeFolders;    // only ConfigBase here
		// append $this->nadlibRoot before each
		//if (basename(getcwd()) != 'be') {
		foreach ($folders as &$el) {
			$el = $this->al->nadlibRoot . $el;
		}
		/*} else {
			foreach ($folders as &$el) {
				$el = '../'. $el;
			}
			$folders[] = '../../../../class';	  // include Config from nadlib/be
			$folders[] = '../../../../model';	  // include User from nadlib/be
		}*/
		TaylorProfiler::stop(__METHOD__);
		return $folders;
	}

	public function addFolder($path, $namespace = null)
	{
		TaylorProfiler::start(__METHOD__);
		if (!Path::isItAbsolute($path)) {
			$path = getcwd() . '/' . $path;
		}
		$this->folders[$namespace][] = realpath($path);
		$sub = glob(cap($path) . '*', GLOB_ONLYDIR);
		//debug($this->folders, $path, $sub);
		if ($sub) {
			foreach ($sub as $s) {
				$this->addFolder($s, $namespace);
			}
		}
		$this->folders = unique_multidim_array_thru($this->folders);
		//pre_print_r($path, $namespace, $this->folders);
		TaylorProfiler::stop(__METHOD__);
	}

	/**
	 * Called to autoload a class from a namespace
	 * @param string $className
	 * @param string $namespace
	 * @return string
	 */
	public function findInFolders($className, $namespace)
	{
		TaylorProfiler::start(__METHOD__ . ' - ' . $className);
		//pre_var_dump($className, $namespace);
		//$appRoot = class_exists('Config') ? $this->config->appRoot : '';
		//foreach ($this->folders as $namespace => $map) {
		$map = ifsetor(
			$this->folders[$namespace],
			$this->folders[null]
		);
		assert(sizeof($map));
//		pre_print_r(
//			array_keys($this->folders),
//			$map,
//			sizeof($map));
		$this->log('Searching for ' . $className . ' [' . $namespace . '] between ' . sizeof($map) . ' folders');
//		pre_print_r($map);
		foreach ($map as $path) {
			$file =
				//dirname(__FILE__).DIRECTORY_SEPARATOR.
				//dirname($_SERVER['SCRIPT_FILENAME']).DIRECTORY_SEPARATOR.
				//$this->nadlibRoot.
				$path . DIRECTORY_SEPARATOR .
				//cap($namespace).//DIRECTORY_SEPARATOR.
				'class.' . $className . '.php';
			$file2 = str_replace(DIRECTORY_SEPARATOR . 'class.', DIRECTORY_SEPARATOR, $file);

			//pre_print_r($file, $file2, file_exists($file), file_exists($file2));

			// pre-check for file without "class." prefix
			if (!file_exists($file)) {
				if (file_exists($file2)
					&& !(
						basename($file2) == 'Index.php'
						&& basename(dirname(realpath($file2))) == 'nadlib'
					)
				) {    // on windows exclude index.php
					$file = $file2;
				} else {
					//$file = $file;
				}
			} else {
				$file2 = null;
			}

			// Index != index.php on Windows
			if ($className === 'Index') {
//				pre_print_r([$className, $file, basename($file)]);
				if (basename(realpath($file)) != ($className . '.php')) {
					$file = null;
				}
			}

			//echo $file, ': ', file_exists($file) ? 'YES' : '-', BR;
			if (file_exists($file)) {
				$this->logSuccess($className . ' ' . $file . ': YES');
				$this->logSuccess($className . ' ' . $file2 . ': YES');
				//pre_var_dump('Found', $file);
				return $file;
			} else {
				$this->logError($className . ' ' . $file . ': no');
				$this->logError($className . ' ' . $file2 . ': no');
			}
		}
		if ($this->debug) {
			//debug($className, $namespace, $map);
			$this->log(__METHOD__ . ': Attempt to find ' . $namespace . '\\' . $className . ' failed');
		}
		TaylorProfiler::stop(__METHOD__);
		return null;
	}

	public function log($debugLine)
	{
		if ($this->collectDebug !== null) {
			$this->collectDebug[] = $debugLine;
		} elseif ($this->debug) {
			if (Request::isCLI()) {
				echo strip_tags($debugLine), BR;
			} else {
				echo $debugLine, BR;
			}
		}
	}

	public function logSuccess($message)
	{
		$message = '<span style="color: red;">' . $message . '</span>';
		$this->log($message);
	}

	public function logError($message)
	{
		$message = '<span style="color: red;">' . $message . '</span>';
		$this->log($message);
	}

	public function clearCache()
	{
		// __destruct will overwrite
		$this->saveFolders = false;
	}

	public function __destruct()
	{
		if ($this->saveFolders) {
			$_SESSION[__CLASS__]['folders'] = $this->folders;
		} else {
			$_SESSION[__CLASS__]['folders'] = null;
		}
	}

}
