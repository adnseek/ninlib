<?php

class NadlibIndex
{

	/**
	 * @var NadlibIndex
	 */
	public static $instance;
	/**
	 * @var Request
	 */
	protected $request;
	/**
	 * @var DIContainer
	 */
	protected $dic;

	public function __construct()
	{
		if (file_exists('vendor/autoload.php')) {
			require_once 'vendor/autoload.php';
		} elseif (file_exists('../vendor/autoload.php')) {
			require_once '../vendor/autoload.php';
		} elseif (file_exists('../../vendor/autoload.php')) {
			require_once '../../vendor/autoload.php';
		} elseif (file_exists('../../../vendor/autoload.php')) {
			require_once '../../../vendor/autoload.php';
		}

		require_once 'init.php';
		$in = new InitNADLIB();
		$in->init();

		$this->dic = new DIContainer();
		$this->dic->index = function ($c) {
			require_once __DIR__ . '/../be/class/IndexBE.php';
			$indexBE = IndexBE::getInstance(true);
			return $indexBE;
		};
		$this->dic->debug = function ($c) {
			return new Debug($c->index);
		};
		$this->dic->config = function ($c) {
			return ConfigBE::getInstance();
		};
		$this->dic->autoload = function ($c) {
			return AutoLoad::getInstance();
		};

		if (!class_exists('Config')) {
			require_once __DIR__ . '/../be/class/ConfigBE.php';
//			class_alias('ConfigBE', 'Config');
		}
		if (!class_exists('AppController', false)) {
			if (!class_exists('AppController')) {
				//class_alias('Controller', 'AppController');
			}
//			class_alias('AppController', 'AppControllerME');
		}
		if (!class_exists('Index')) {
//			class_alias('IndexBE', 'Index');
		}

		if (!file_exists('vendor/autoload.php')) {
			//throw new Exception('Run "composer update"');
		}
		$this->dic->config->defaultController = 'HomeBE';

		self::$instance = $this;
	}

	public function render()
	{
		if (Request::isCLI()) {
			$content[] = $this->cliMode();
		} else {
			chdir('be');
			//echo ($this->dic->autoload->nadlibFromCWD);
			//$this->dic->autoload->nadlibFromCWD = '../'.$this->dic->autoload->nadlibFromCWD;
			//echo ($this->dic->autoload->nadlibFromCWD);

			$i = $this->dic->index;
			/** @var $i IndexBE */
			//echo get_class($i), BR, class_exists('Index'), BR, get_class(Index::getInstance());
			$content[] = $i->render();
		}
		$content = $this->s($content);
		return $content;
	}

	public function cliMode()
	{
		$content[] = 'Nadlib CLI mode';
		$this->request->importCLIparams();
		if ($cmd = $this->request->getTrim('0')) {
			$cmdAction = $cmd . 'Action';
			if (method_exists($this, $cmdAction)) {
				$content[] = $this->$cmdAction();
			}
		} else {
			throw new InvalidArgumentException('"' . $cmd . '" is unknown');
		}
		return $content;
	}

	public function s($content)
	{
		return MergedContent::mergeStringArrayRecursive($content);
	}

	public function initAction()
	{
		return 'initAction';
	}

}
