<?php

require_once __DIR__ . '/ConfigInterface.php';

/**
 * Class ConfigBase - a Config, Singleton, Factory, Repository, DependencyInjectionContainer and Locator in one class.
 * Extend with a name Class and add any configuration parameters and factory calls.
 * @phpstan-consistent-constructor
 */
class ConfigBase implements ConfigInterface
{
	/**
	 * del: Public to allow Request to know if there's an instance
	 * @var Config
	 */
	protected static $instance;

	public $db_server = '127.0.0.1';
	public $db_user = 'root';
	protected $db_password = 'root';

	public $db_database = '';

	/**
	 * @var int
	 * @deprecated in favor of $this->config['Config']['timeLimit'] in init.php
	 */
	public $timeLimit = 10;

	/**
	 *
	 * @var DBInterface
	 */
	protected $db;

	public $defaultController = 'Overview';

	/**
	 * @var Path
	 */
	public $documentRoot;

	public static $includeFolders = [
		'.',
		'Base',
		'Cache',
		'Chart',
		'Controller',
		'CSS',
		'Data',
		'DB',
		'DB/Driver',
		'DB/Iterator',
		'Debug',
		'Geo',
		'HTML',
		'js',
		'HTMLForm',
		'HTTP',
		'LocalLang',
		'ORM',
		'Runner',
		'SQL',
		'Time',
		'User',
		'be/class',
		'be/class/DB',
		'be/class/Info',
		'be/class/Test',
		'Queue',
	];

	/**
	 * Enables FlexiTable check if the all the necessary tables/columns exist.
	 * Disable for performance.
	 *
	 * @var bool
	 */
	public $flexiTable = false;

	/**
	 * Read from config.json
	 * @var array
	 */
	public $config;

	/**
	 * @var UserModelInterface
	 */
	protected $user;

	public $mailFrom = '';

	/**
	 * @var LocalLang
	 */
	public $ll;

	public $isCron = false;

	/** @phpstan-consistent-constructor */
	protected function __construct()
	{
		if (isset($_REQUEST['d']) && $_REQUEST['d'] == 'log') {
			echo __METHOD__ . "<br />\n";
		}
		$this->documentRoot = Request::getDocumentRoot();
//		debug($this->documentRoot);

		$appRoot = AutoLoad::getInstance()->getAppRoot();
		0 && pre_print_r([
			'Config->documentRoot' => $this->documentRoot,
			'Config->appRoot' => $appRoot,
		]);
		//debug_pre_print_backtrace();

		//print_r(array(getcwd(), 'class/config.json', file_exists('class/config.json')));
		$configYAML = $appRoot . 'class/config.yaml';
		//print_r(array($configYAML, file_exists($configYAML)));
		if (file_exists($configYAML) && class_exists('Spyc')) {
			$this->config = Spyc::YAMLLoad($configYAML);
		}
		$this->mergeConfig($this);

		$configJSON = $appRoot . 'class/config.json';
		//print_r(array($configJSON, file_exists($configJSON)));
		if (file_exists($configJSON)) {
			$this->config = json_decode(file_get_contents($configJSON), true);
			$this->mergeConfig($this);
		}
		$this->isCron = Request::isCron();
		if (isset($_REQUEST['d']) && $_REQUEST['d'] == 'log') {
			echo __METHOD__ . BR;
		}
	}

	public static function hasInstance()
	{
		return self::$instance;
	}

	/**
	 * For compatibility with PHPUnit you need to call
	 * Config::getInstance()->postInit() manually
	 * @return static
	 */
	public static function getInstance()
	{
		if (!self::$instance) {
			self::$instance = new static();
			//self::$instance->postInit();	// will try to connect to the DB before autoload
			// must be called outside
			assert(self::$instance instanceof ConfigBase);
		}
		return self::$instance;
	}

	/**
	 * Does heavy operations during bootstrapping
	 * @return $this
	 */
	public function postInit()
	{
		//$this->getDB();
		// init user here as he needs to access Config::getInstance()
		$this->user = null;
		return $this;
	}

	public function getDB()
	{
		//debug_pre_print_backtrace();
		if ($this->db) {
			return $this->db;
		}

		if ($this->db_database) {
			if (extension_loaded('pdo_mysql')) {
				$this->db = new DBLayerPDO(
					$this->db_database,
					$this->db_server,
					$this->db_user,
					$this->db_password,
					'mysql',
					''
				);
				$this->db->perform('set names utf8');
			} elseif (extension_loaded('mysql')) {
				$this->db = new MySQL(
					$this->db_database,
					$this->db_server,
					$this->db_user,
					$this->db_password
				);
			} else {
				throw new DatabaseException('Please enable PDO');
			}
			$this->db->setQb(new SQLBuilder($this->db));
		}
		return $this->db;
	}

	public function prefixTable($a)
	{
		return $a;
	}

	/**
	 * TODO: enable FirePHP
	 * @param string $class
	 * @param mixed $message
	 * @throws Exception
	 */
	public function log($class, $message)
	{
		if (DEVELOPMENT) {
			throw new Exception($class . ' ' . $message);
		}
	}

	/**
	 * @param object $obj
	 */
	public function mergeConfig($obj)
	{
		$class = get_class($obj);
		if (isset($this->config[$class]) && is_array($this->config[$class])) {
			foreach ($this->config[$class] as $key => $val) {
				// Strict Standards: Accessing static property Config::$includeFolders as non static
				if ($key != 'includeFolders') {
					$obj->$key = $val;
				}
			}
		}
	}

	/**
	 * @return UserModelInterface
	 * @throws LoginException
	 */
	public function getUser()
	{
		if (is_object($this->user)) {
			return $this->user;
		} else {
			throw new LoginException(__METHOD__);
		}
	}

	/**
	 * Convenience function example how to use Login
	 * @return UserModelInterface
	 * @throws DatabaseException
	 */
	public function _getLoginUser()
	{
		if (!$this->user) {
			$db = $this->getDB();
//			debug(get_class($db));
			$this->user = new BEUser($db);
			try {
				$this->user->try2login(null, null);
			} catch (Exception $e) {
				// failed to login - no problem
			}
		}
		return $this->user;
	}

	public function getLL()
	{
		if (!$this->ll) {
			$this->ll = new LocalLangDummy();
		}
		return $this->ll;
	}

	public function getRequest()
	{
		return Request::getInstance();
	}

	public function getDBpassword()
	{
		return $this->db_password;
	}

	public function setUser(UserModelInterface $user)
	{
		$this->user = $user;
	}
}
