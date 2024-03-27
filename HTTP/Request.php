<?php

require_once __DIR__ . '/URL.php';

use nadlib\HTTP\Session;
use spidgorny\nadlib\HTTP\URL;

/**
 * @phpstan-consistent-constructor
 */
class Request
{
	/**
	 * Singleton
	 * @var Request
	 */
	protected static $instance;
	/**
	 * @var URL
	 */
	public $url;
	/**
	 * Assoc array of URL parameters
	 * @var array
	 */
	protected $data = [];
	protected $proxy;

	/**
	 * @param array|null $array
	 */
	public function __construct(array $array = null)
	{
		$this->data = !is_null($array) ? $array : $_REQUEST;
		$this->url = new URL(
			isset($_SERVER['SCRIPT_URL'])
				? $_SERVER['SCRIPT_URL']
				: (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null),
			$this->data
		);
	}

	public static function getExistingInstance()
	{
		return static::$instance;
	}

	public static function isJenkins()
	{
		return ifsetor($_SERVER['BUILD_NUMBER'], getenv('BUILD_NUMBER'));
	}

	public static function getLocationDebug()
	{
		$docRoot = self::getDocRoot();
		ksort($_SERVER);
		pre_print_r([
			'docRoot' => $docRoot . '',
			'PHP_SELF' => $_SERVER['PHP_SELF'],
			'cwd' => getcwd(),
			'url' => self::getLocation() . '',
			'server' => array_filter($_SERVER, function ($el) {
				return is_string($el) && strpos($el, '/') !== false;
			}),
		]);
	}

	public static function getPort()
	{
		$host = isset($_SERVER['HTTP_X_FORWARDED_HOST'])
			? $_SERVER['HTTP_X_FORWARDED_HOST']
			: (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null);
		$host = trimExplode(':', $host);    // localhost:8081
		$port = $host[1];
		return $port;
	}

	public static function removeCookiesFromRequest()
	{
		if (false !== strpos(ini_get('variables_order'), 'C')) {
			//debug($_COOKIE, ini_get('variables_order'));
			foreach ($_COOKIE as $key => $_) {
				if (!isset($_GET[$key]) && !isset($_POST[$key])) {
					unset($_REQUEST[$key]);
				}
			}
		}
	}

	public static function isCURL()
	{
		$isCURL = str_contains(ifsetor($_SERVER['HTTP_USER_AGENT']), 'curl');
		return $isCURL;
	}

	/**
	 * http://stackoverflow.com/questions/190759/can-php-detect-if-its-run-from-a-cron-job-or-from-the-command-line
	 * @return bool
	 */
	public static function isCron()
	{
		return !self::isPHPUnit()
			&& self::isCLI()
			&& !isset($_SERVER['TERM'])
			&& !self::isWindows();
	}

	public static function isPHPUnit()
	{
		//debug($_SERVER); exit();
		$phpunit = defined('PHPUnit');
		$phar = !!ifsetor($_SERVER['IDE_PHPUNIT_PHPUNIT_PHAR']);
		$loader = !!ifsetor($_SERVER['IDE_PHPUNIT_CUSTOM_LOADER']);
		$phpStorm = basename($_SERVER['PHP_SELF']) == 'ide-phpunit.php';
		$phpStorm2 = basename($_SERVER['PHP_SELF']) == 'phpunit';
		return $phar || $loader || $phpStorm || $phpStorm2 || $phpunit;
	}

	public static function printDocumentRootDebug()
	{
		pre_print_r([
			'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'],
			'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'],
			'PHP_SELF' => $_SERVER['PHP_SELF'],
			'cwd' => getcwd(),
			'getDocumentRootByRequest' => self::getDocumentRootByRequest(),
			'getDocumentRootByDocRoot' => self::getDocumentRootByDocRoot(),
			'getDocumentRootByScript' => self::getDocumentRootByScript(),
			'getDocumentRootByIsDir' => self::getDocumentRootByIsDir(),
			'getDocumentRoot' => self::getDocumentRoot() . '',
		]);
	}

	/**
	 * @return mixed|string
	 * //~depidsvy/something
	 */
	private static function getDocumentRootByScript()
	{
		$script = $_SERVER['SCRIPT_FILENAME'];
		$pos = strpos($script, '/public_html');
		if ($pos !== false) {
			$docRoot = substr(dirname($script), $pos);
			$docRoot = str_replace('public_html', '~depidsvy', $docRoot);
			return $docRoot;
		}

		return dirname($_SERVER['PHP_SELF']);
	}

	public static function getDocumentRootByIsDir()
	{
		$result = self::dir_of_file(
			self::firstExistingDir(
				ifsetor($_SERVER['REQUEST_URI'])
			)
		);
		return cap($result);
	}

	/**
	 * dirname('/53/') = '/' which is a problem
	 * @param $path
	 * @return string
	 */
	public static function dir_of_file($path)
	{
		if ($path[strlen($path) - 1] == '/') {
			return substr($path, 0, -1);
		}

		return dirname($path);
	}

	public static function firstExistingDir($path)
	{
		$check = $_SERVER['DOCUMENT_ROOT'] . $path;
		//		error_log($check);
		if (is_dir($check)) {
			return cap(rtrim($path, '\\'), '/');
		}

		if ($path) {
			//echo $path, BR;
			return self::firstExistingDir(self::dir_of_file($path));
		}

		return '/';
	}

	public static function isHTTPS()
	{
		return self::getRequestType() === 'https';
	}

	public static function isCalledScript($__FILE__)
	{
		if (ifsetor($_SERVER['SCRIPT_FILENAME'])) {
			return $__FILE__ == $_SERVER['SCRIPT_FILENAME'];
		} else {
			throw new Exception(__METHOD__);
		}
	}

	public static function isLocalhost()
	{
		$host = self::getOnlyHost();
		if (in_array($host, ['localhost', '127.0.0.1'])) {
			return true;
		}
		$hostname = gethostname();
		if ($host == $hostname) {
			return true;
		}
		return false;
	}

	public static function getOnlyHost()
	{
		$host = self::getHost();
		if (str_contains($host, ':')) {
			$host = first(trimExplode(':', $host));    // localhost:8081
		}
		return $host;
	}

	/**
	 * Will overwrite
	 * @param $var
	 * @param $val
	 */
	public function set($var, $val)
	{
		$this->data[$var] = $val;
	}

	public function un_set($name)
	{
		unset($this->data[$name]);
	}

	public function string($name)
	{
		return $this->getString($name);
	}

	public function getString($name)
	{
		return isset($this->data[$name]) ? strval($this->data[$name]) : '';
	}

	public function getTrimLower($name)
	{
		return strtolower($this->trim($name));
	}

	public function trim($name)
	{
		return $this->getTrim($name);
	}

	/**
	 * Will strip tags
	 * @param $name
	 * @return string
	 * @throws Exception
	 */
	public function getTrimRequired($name)
	{
		$value = $this->getString($name);
		$value = strip_tags($value);
		$value = trim($value);
		if (!$value) {
			throw new InvalidArgumentException('Parameter ' . $name . ' is required.');
		}
		return $value;
	}

	/**
	 * Checks that trimmed value isset in the supplied array
	 * @param $name
	 * @param array $options
	 * @return string
	 * @throws Exception
	 */
	public function getOneOf($name, array $options)
	{
		$value = $this->getTrim($name);
		if (!isset($options[$value])) {
			//debug($value, $options);
			throw new Exception(__METHOD__ . ' is throwing an exception.');
		}
		return $value;
	}

	/**
	 * General filtering function
	 * @param $name
	 * @return string
	 */
	public function getTrim($name)
	{
		$value = $this->getString($name);
		$value = strip_tags($value);
		$value = trim($value);
		return $value;
	}

	/**
	 * Checks for keys, not values
	 *
	 * @param $name
	 * @param array $assoc - only array keys are used in search
	 * @return int|null
	 */
	public function getIntIn($name, array $assoc)
	{
		$id = $this->getIntOrNULL($name);
		if (!is_null($id) && !in_array($id, array_keys($assoc))) {
			$id = null;
		}
		return $id;
	}

	public function getIntOrNULL($name)
	{
		return $this->is_set($name) ? $this->int($name) : null;
	}

	public function is_set($name)
	{
		return isset($this->data[$name]);
	}

	public function int($name)
	{
		return isset($this->data[$name]) ? intval($this->data[$name]) : 0;
	}

	public function getIntInException($name, array $assoc)
	{
		$id = $this->getIntOrNULL($name);
		if (!is_null($id) && !in_array($id, array_keys($assoc))) {
			debug($id, array_keys($assoc));
			throw new InvalidArgumentException($name . ' is not part of allowed collection.');
		}
		return $id;
	}

	public function getIntRequired($name)
	{
		$id = $this->getIntOrNULL($name);
		if (!$id) {
			throw new InvalidArgumentException($name . ' parameter is required.');
		}
		return $id;
	}

	public function getFloat($name)
	{
		return floatval($this->data[$name]);
	}

	/**
	 * Will return timestamp
	 * Converts string date compatible with strtotime() into timestamp (integer)
	 *
	 * @param string $name
	 * @return int
	 * @throws Exception
	 */
	public function getTimestampFromString($name)
	{
		$string = $this->getTrim($name);
		$val = strtotime($string);
		if ($val == -1) {
			throw new Exception("Invalid input for date ($name): $string");
		}
		return $val;
	}

	public function getTrimArray($name)
	{
		$list = $this->getArray($name);
		if ($list) {
			$list = array_map('trim', $list);
		}
		return $list;
	}

	/**
	 * @param $name
	 * @return array
	 */
	public function getArray($name)
	{
		return isset($this->data[$name]) ? (array)($this->data[$name]) : [];
	}

	public function getArrayByPath(array $name)
	{
		$subRequest = $this->getSubRequestByPath($name);
		return $subRequest->getAll();
	}

	public function getSubRequestByPath(array $name)
	{
		$current = $this;
		reset($name);
		do {
			$next = current($name);
			$current = $current->getSubRequest($next);
			//debug($name, $next, $current->getAll());
		} while (next($name));
		return $current;
	}

	/**
	 * Similar to getArray() but the result is an object of a Request
	 * @param $name
	 * @return Request
	 */
	public function getSubRequest($name)
	{
		return new Request($this->getArray($name));
	}

	public function getAll()
	{
		return $this->data;
	}

	/**
	 * Makes sure it's an integer
	 * @param string $name
	 * @return int
	 */
	public function getTimestamp($name)
	{
		return $this->getInt($name);
	}

	public function getInt($name)
	{
		return $this->int($name);
	}

	/**
	 * Will return Time object
	 *
	 * @param string $name
	 * @param null $rel
	 * @return Time
	 */
	public function getTime($name, $rel = null)
	{
		if ($this->is_set($name) && $this->getTrim($name)) {
			return new Time($this->getTrim($name), $rel);
		}
		return null;
	}

	/**
	 * Will return Date object
	 *
	 * @param string $name
	 * @param null $rel
	 * @return Date
	 */
	public function getDate($name, $rel = null)
	{
		if ($this->is_set($name) && $this->getTrim($name)) {
			return new Date($this->getTrim($name), $rel);
		}
		return null;
	}

	public function getFile($name, $prefix = null, $prefix2 = null)
	{
		$files = $prefix ? $_FILES[$prefix] : $_FILES;
		//debug($files);
		if ($prefix2 && $files) {
			foreach ($files as &$row) {
				$row = $row[$prefix2];
			}
		}
		if ($files) {
			foreach ($files as &$row) {
				$row = $row[$name];
			}
		}
		//debug($files);
		return $files;
	}

	/**
	 * Opposite of getSubRequest. It's a way to reimplement a subrequest
	 * @param $name
	 * @param Request $subrequest
	 * @return $this
	 */
	public function import($name, Request $subrequest)
	{
		foreach ($subrequest->data as $key => $val) {
			$this->data[$name][$key] = $val;
		}
		return $this;
	}

	/**
	 * Returns item identified by $a or an alternative value
	 * @param $a
	 * @param $value
	 * @return string
	 */
	public function getCoalesce($a, $value)
	{
		$a = $this->getTrim($a);
		return $a ? $a : $value;
	}

	/**
	 * List getCoalesce() but reacts on attempt to unset the value
	 * @param $a        string
	 * @param $default    string
	 * @return string
	 */
	public function ifsetor($a, $default)
	{
		if ($this->is_set($a)) {
			$value = $this->getTrim($a);
			return $value;    // returns even if empty
		} else {
			return $default;
		}
	}

	public function setNewController($class)
	{
		$this->data['c'] = $class;
	}

	public function getRefererIfNotSelf()
	{
		$referer = $this->getReferer();
		$rController = $this->getRefererController();
		$index = Index::getInstance();
		$cController = $index->controller
			? get_class($index->controller)
			: Config::getInstance()->defaultController;
		$ok = (($rController != $cController) && ($referer . '' != new URL() . ''));
		//debug($rController, __CLASS__, $ok);
		return $ok ? $referer : null;
	}

	public function getReferer()
	{
		if (ifsetor($_SERVER['HTTP_REFERER'])) {
			$url = new URL($_SERVER['HTTP_REFERER']);
		} else {
			$url = null;
		}
		return $url;
	}

	public function getRefererController()
	{
		$return = null;
		$url = $this->getReferer();
		if ($url) {
			$url->setParams([]);   // get rid of any action
			$rr = $url->getRequest();
			$return = $rr->getControllerString();
		}
		//debug($_SERVER['HTTP_REFERER'], $url, $rr, $return);
		return $return;
	}

	//

	public function getControllerString($returnDefault = true)
	{
		if ($this->isCLI()) {
			$resolver = new CLIResolver();
			return $resolver->getController();
		}

		$c = $this->getTrim('c');
		if ($c) {
			$resolver = new CResolver($c);
			$controller = $resolver->getController();
		} else {
			$resolver = new PathResolver();
			$controller = $resolver->getController($returnDefault);
		}   // cli
		nodebug([
			'result' => $controller,
			'c' => $this->getTrim('c'),
			//'levels' => $this->getURLLevels(),
			'default' => class_exists('Config')
				? Config::getInstance()->defaultController
				: null,
			'data' => $this->data]);
		return $controller;
	}

	public static function isCLI()
	{
		//return isset($_SERVER['argc']);
		return php_sapi_name() == 'cli';
	}

	/**
	 * Will require modifications when realurl is in place
	 *
	 * @return SimpleController|Controller
	 * @throws Exception
	 */
	public function getController()
	{
		$ret = null;
		$c = $this->getControllerString();
		if (!$c) {
			$c = Index::getInstance()->controller; // default
		}
		if (!is_object($c)) {
			if (class_exists($c)) {
				$ret = new $c();
			} elseif ($c) {
				throw new Exception('Class ' . $c . ' can\'t be found.');
			}
		}
		return $ret;
	}

	public static function getInstance($cons = null)
	{
		if (!static::$instance) {
			static::$instance = new static($cons);
		}
		return static::$instance;
	}

	public function redirectFromAjax($relative)
	{
		if (str_startsWith($relative, 'http')) {
			$link = $relative;
		} else {
			$link = $this->getLocation() . $relative;
		}
		if (!headers_sent()) {
			header('X-Redirect: ' . $link);    // to be handled by AJAX callback
			exit();
		}

		$this->redirectJS($link);
	}

	/**
	 * Returns the full URL to the document root of the current site
	 * @param bool $isUTF8
	 * @return URL
	 */
	public static function getLocation($isUTF8 = false)
	{
		$docRoot = self::getDocRoot();
//		llog($docRoot.'');
		$host = self::getHost($isUTF8);
		$url = Request::getRequestType() . '://' . $host . $docRoot;
		return new URL($url);
	}

	/**
	 * @return Path
	 */
	public static function getDocRoot()
	{
		$docRoot = null;
		if (class_exists('Config')) {
			$c = Config::getInstance();
			$docRoot = $c->documentRoot;
		}
		if (!$docRoot) {
			$docRoot = self::getDocumentRoot();
		}
		//pre_print_r($docRoot);

		if (!str_startsWith($docRoot, '/')) {
			$docRoot = '/' . $docRoot;
		}

		if (!($docRoot instanceof Path)) {
			$docRoot = new Path($docRoot);
		}

		return $docRoot;
	}

	/**
	 * [DOCUMENT_ROOT]      => U:/web
	 * [SCRIPT_FILENAME]    => C:/Users/DEPIDSVY/NetBeansProjects/merged/index.php
	 * [PHP_SELF]           => /merged/index.php
	 * [cwd]                => C:\Users\DEPIDSVY\NetBeansProjects\merged
	 * @return Path
	 */
	public static function getDocumentRoot()
	{
		// PHP Warning:  strpos(): Empty needle in /var/www/html/vendor/spidgorny/nadlib/HTTP/class.Request.php on line 706

		$docRoot = self::getDocumentRootByRequest();
		if (!$docRoot || ('/' === $docRoot)) {
			$docRoot = self::getDocumentRootByDocRoot();
		}

		// this is not working right
		//		if (!$docRoot || ('/' == $docRoot)) {
		//			$docRoot = self::getDocumentRootByScript();
		//		}

		//		$before = $docRoot;
		//$docRoot = str_replace(AutoLoad::getInstance()->nadlibFromDocRoot.'be', '', $docRoot);	// remove vendor/spidgorny/nadlib/be
		$docRoot = cap($docRoot, '/');
		//debug($_SERVER['DOCUMENT_ROOT'], dirname($_SERVER['SCRIPT_FILENAME']), $before, AutoLoad::getInstance()->nadlibFromDocRoot.'be', $docRoot);
		//print '<pre>'; print_r(array($_SERVER['DOCUMENT_ROOT'], dirname($_SERVER['SCRIPT_FILENAME']), $before, $docRoot)); print '</pre>';

		//debug_pre_print_backtrace();
		require_once __DIR__ . '/Path.php'; // needed if called early
		$docRoot = new Path($docRoot);
		//pre_print_r($docRoot, $docRoot.'');
		return $docRoot;
	}

	/**
	 * Works well with RewriteRule
	 */
	public static function getDocumentRootByRequest()
	{
		$script = $_SERVER['SCRIPT_FILENAME'];
		$request = dirname(ifsetor($_SERVER['REQUEST_URI'], ''));
		//		exit();
		if ($request && $request !== '/' && strpos($script, $request) !== false) {
			$docRootRaw = $_SERVER['DOCUMENT_ROOT'];
			$docRoot = str_replace($docRootRaw, '', dirname($script)) . '/';    // dirname() removes slash
		} else {
			$docRoot = '/';
		}
		//		pre_print_r($script, $request, strpos($script, $request), $docRoot);
		return $docRoot;
	}

	public static function getDocumentRootByDocRoot()
	{
		$docRoot = null;
		$script = $_SERVER['SCRIPT_FILENAME'];
		$docRootRaw = ifsetor($_SERVER['DOCUMENT_ROOT']);
		if (!empty($docRootRaw)) {
			$beginTheSame = str_startsWith($script, $docRootRaw);
			$contains = strpos($script, $docRootRaw) !== false;
		} else {
			$beginTheSame = false;
			$contains = false;
		}
		if ($docRootRaw
			&& $beginTheSame
			&& $contains
		) {
			$docRoot = str_replace($docRootRaw, '', dirname($script) . '/');    // slash is important
			//pre_print_r($docRoot);
		}
		0 && pre_print_r([
			'script' => $script,
			'docRootRaw' => $docRootRaw,
			'beginTheSame' => $beginTheSame,
			'contains' => $contains,
			'replaceFrom' => dirname($script),
			'docRoot' => $docRoot,
		]);
		return $docRoot;
	}

	public static function getHost($isUTF8 = false)
	{
		if (self::isCLI()) {
			return gethostname();
		}
		$host = ifsetor($_SERVER['HTTP_X_ORIGINAL_HOST']);
		if (!$host) {
			$host = isset($_SERVER['HTTP_X_FORWARDED_HOST'])
				? $_SERVER['HTTP_X_FORWARDED_HOST']
				: (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null);
		}
		if (function_exists('idn_to_utf8') && $isUTF8) {
			if (phpversion() >= 7.3) {
				$try = idn_to_utf8($host, 0, defined('INTL_IDNA_VARIANT_UTS46') ? INTL_IDNA_VARIANT_UTS46 : 1);
			} else {
				$try = idn_to_utf8($host);
			}
			//debug($host, $try);
			if ($try) {
				$host = $try;
			}
		}
		return $host;
	}

	/**
	 * http://www.zen-cart.com/forum/showthread.php?t=164174
	 */
	public static function getRequestType()
	{
		$HTTPS = ifsetor($_SERVER['HTTPS'], getenv('HTTPS'));
		$HTTP_X_FORWARDED_HOST = ifsetor($_SERVER['HTTP_X_FORWARDED_HOST']);
		$HTTPS_SERVER = ifsetor($_SERVER['HTTPS_SERVER']);
		$HTTP_X_FORWARDED_SSL = ifsetor($_SERVER['HTTP_X_FORWARDED_SSL']);
		$HTTP_X_FORWARDED_PROTO = ifsetor($_SERVER['HTTP_X_FORWARDED_PROTO']);
		$HTTP_X_FORWARDED_BY = ifsetor($_SERVER['HTTP_X_FORWARDED_BY']);
		$HTTP_X_FORWARDED_SERVER = ifsetor($_SERVER['HTTP_X_FORWARDED_SERVER']);
		$request_type =
			((($HTTPS) && (strtolower($HTTPS) == 'on' || $HTTPS == '1'))) ||
			(($HTTP_X_FORWARDED_BY) && strpos(strtoupper($HTTP_X_FORWARDED_BY), 'SSL') !== false) ||
			(($HTTP_X_FORWARDED_HOST) && (strpos(strtoupper($HTTP_X_FORWARDED_HOST), 'SSL') !== false)) ||
			(($HTTP_X_FORWARDED_HOST && $HTTPS_SERVER) && (strpos(strtoupper($HTTP_X_FORWARDED_HOST), str_replace('https://', '', $HTTPS_SERVER)) !== false)) ||
			(isset($_SERVER['SCRIPT_URI']) && strtolower(substr($_SERVER['SCRIPT_URI'], 0, 6)) == 'https:') ||
			(($HTTP_X_FORWARDED_SSL) && ($HTTP_X_FORWARDED_SSL == '1' || strtolower($HTTP_X_FORWARDED_SSL) == 'on')) ||
			(($HTTP_X_FORWARDED_PROTO) && (strtolower($HTTP_X_FORWARDED_PROTO) == 'ssl' || strtolower($HTTP_X_FORWARDED_PROTO) == 'https')) ||
			(isset($_SERVER['HTTP_SSLSESSIONID']) && $_SERVER['HTTP_SSLSESSIONID'] != '') ||
			(isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443') ||
			ifsetor($_SERVER['FAKE_HTTPS'])
			|| (str_startsWith($HTTP_X_FORWARDED_SERVER, 'sslproxy'))    // BlueMix
				? 'https' : 'http';
		return $request_type;
	}

	public function redirectJS(
		$controller, $delay = 0, $message =
	'Redirecting to %1'
	)
	{
		echo __($message, '<a href="' . $controller . '">' . $controller . '</a>') . '
			<script>
				setTimeout(function () {
					document.location = "' . $controller . '";
				}, ' . $delay . ');
			</script>';
	}

	/**
	 * http://php.net/manual/en/function.apache-request-headers.php#70810
	 * @return bool
	 */
	public function isAjax()
	{
		$headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
		if (!$headers) {
			$headers = [
				'X-Requested-With' => ifsetor($_SERVER['HTTP_X_REQUESTED_WITH'])
			];
		}
		$headers = array_change_key_case($headers, CASE_LOWER);

		$isXHR = false;
		if (isset($headers['x-requested-with'])) {
			$isXHR = $headers['x-requested-with'] === 'XMLHttpRequest';
		}
		return $this->getBool('ajax') || $isXHR;
	}

	public function getBool($name)
	{
		return $this->bool($name);
	}

	public function bool($name)
	{
		return (isset($this->data[$name]) && $this->data[$name]) ? true : false;
	}

	public function getHeader($name)
	{
		$headers = $this->getHeaders();

		$found = ifsetor($headers[$name]);
		if ($found) {
			return $found;
		}

		foreach ($headers as $header => $value) {
			if (strtolower($header) === strtolower($name)) {
				return $value;
			}
		}
		return null;
	}

	public function getHeaders()
	{
		if (function_exists('apache_request_headers')) {
			return apache_request_headers();
		}
		if (function_exists('getallheaders')) {
			return getallheaders();
		}

		return collect($_SERVER)->filter(static function ($val, $key) {
			return str_startsWith($key, 'HTTP_');
		})->mapWithKeys(static function ($val, $key) {
			$newKey = strtolower($key);
			$newKey = str_replace('_', '-', $newKey);
			return [$newKey => $val];
		})->toArray();
	}

	public function getJson($name, $array = true)
	{
		return json_decode($this->getTrim($name), $array);
	}

	public function getJSONObject($name)
	{
		return json_decode($this->getTrim($name), false);
	}

	public function isSubmit()
	{
		return $this->isPOST() || $this->getBool('submit') || $this->getBool('btnSubmit');
	}

	public function isPOST()
	{
		return ifsetor($_SERVER['REQUEST_METHOD']) == 'POST';
	}

	public function getDateFromYMD($name)
	{
		$date = $this->getInt($name);
		if ($date) {
			$y = substr($date, 0, 4);
			$m = substr($date, 4, 2);
			$d = substr($date, 6, 2);
			$date = strtotime("$y-$m-$d");
			$date = new Date($date);
		} else {
			$date = null;
		}
		return $date;
	}

	public function getDateFromY_M_D($name)
	{
		$date = $this->getTrim($name);
		$date = strtotime($date);
		return $date;
	}

	public function getMethod()
	{
		return ifsetor($_SERVER['REQUEST_METHOD']);
	}

	/**
	 * Will overwrite one by one.
	 * @param array $plus
	 */
	public function setArray(array $plus)
	{
		foreach ($plus as $key => $val) {
			$this->data[$key] = $val;
		}
	}

	public function getPathAfterDocRoot()
	{
		$al = AutoLoad::getInstance();

		if (!$this->isWindows()) {    // linux
			//debug(getcwd(), $al->documentRoot.'');
			//			debug('cwd', $cwd);
			$url = clone $al->documentRoot;
			//			debug('documentRoot', $url);
			$url->append($this->url->getPath());
			$url->normalizeHomePage();

			$cwd = new Path(getcwd());
			$cwd->normalizeHomePage();

			$path = new Path($url);
			$path->remove($cwd);
			$path->normalize();

			//			debug($url.'', $cwd.'', $path.'');
		} else {    // windows
			$cwd = null;
			$url = new Path('');
			$url->append($this->url->getPath());
			$path = new Path($url);

			//			debug($al->documentRoot);
			if (false) {    // doesn't work in ORS
				$path->remove(clone $al->documentRoot);
			} elseif ($al->documentRoot instanceof Path) {        // works in ORS
				$path->remove(clone $al->documentRoot);
			}
			//			debug($url.'', $path.'', $al->documentRoot.'');
		}
		return $path;
	}

	/**
	 * http://stackoverflow.com/questions/738823/possible-values-for-php-os
	 * @return bool
	 */
	public static function isWindows()
	{
		//$os = isset($_SERVER['OS']) ? $_SERVER['OS'] : '';
		//return $os == 'Windows_NT';
		return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
	}

	/**
	 * Overwriting - no
	 * @param array $plus
	 * @return Request
	 */
	public function append(array $plus)
	{
		$this->data += $plus;
		return $this;
	}

	/**
	 * Full URL is docRoot + appRoot + controller/action
	 * @return Path
	 */
	public function getPathAfterAppRoot()
	{
		$al = AutoLoad::getInstance();
		$appRoot = $al->getAppRoot()->normalize()->realPath();
//		$docRoot = $al->documentRoot->normalize()->realPath();
		//		d($appRoot.'', $docRoot.'');

		$pathWithoutDocRoot = clone $appRoot;
		//		$pathWithoutDocRoot->remove($docRoot);

		$path = clone $this->url->getPath()->resolveLinks();
		//		d('remove', $pathWithoutDocRoot.'', 'from', $path.'');
		$path->remove($pathWithoutDocRoot);
		$path->normalize();

		return $path;
	}

	public function baseHrefFromServer()
	{
		$al = AutoLoad::getInstance();
		$appRoot = $al->getAppRoot()->normalize()->realPath();
		$path = new Path($_SERVER['SCRIPT_FILENAME']);
		$path->trimIf($path->basename());
//		llog('remove', $appRoot.'', 'from', $path.'');
		$path->remove($appRoot);
		$path->normalize();
//		llog($path);
//		debug($appRoot.'', $_SERVER['SCRIPT_FILENAME'], $path.'');
		return $path;
	}

	public function baseHref()
	{
		$path = new Path($_SERVER['SCRIPT_FILENAME']);
		$url = new URL($_SERVER['REQUEST_URI']);
		$urlPath = $url->getPath();
		$intersect = array_intersect($path->aPath, $urlPath->aPath);
		llog($path . '', $urlPath . '', $intersect);
		if (count($intersect)) {
			return '/' . implode('/', $intersect) . '/xxx';
		}
		return '/';
	}

	public function setPath($path)
	{
		$this->url->setPath($path);
	}

	public function setBasename($path)
	{
		$this->url->setBasename($path);
	}

	/**
	 * Overwriting - yes
	 * @param array $plus
	 * @return Request
	 */
	public function overwrite(array $plus)
	{
		foreach ($plus as $key => $val) {
			$this->data[$key] = $val;
		}
		return $this;
	}

	/**
	 * http://christian.roy.name/blog/detecting-modrewrite-using-php
	 * @return bool
	 */
	public function apacheModuleRewrite()
	{
		if (function_exists('apache_get_modules')) {
			$modules = apache_get_modules();
			//debug($modules);
			$mod_rewrite = in_array('mod_rewrite', $modules);
		} else {
			$mod_rewrite = getenv('HTTP_MOD_REWRITE') === 'On' ? true : false;
		}
		return $mod_rewrite;
	}

	public function debug()
	{
		return get_object_vars($this);
	}

	/**
	 * Uses realpath() to make sure file exists
	 * @param $name
	 * @return string
	 */
	public function getFilePathName($name)
	{
		$filename = $this->getTrim($name);
		//echo getDebug(getcwd(), $filename, realpath($filename));
		$filename = realpath($filename);
		return $filename;
	}

	/**
	 * Just cuts the folders with basename()
	 * @param $name
	 * @return string
	 */
	public function getFilename($name)
	{
		//filter_var($this->getTrim($name), ???)
		$filename = $this->getTrim($name);
		$filename = basename($filename);
		return $filename;
	}

	public function importCLIparams($noopt = [])
	{
		$this->data += $this->parseParameters($noopt);
		return $this;
	}

	/**
	 * Parses $GLOBALS['argv'] for parameters and assigns them to an array.
	 * @see http://www.php.net/manual/en/function.getopt.php#83414
	 *
	 * Supports:
	 * -e
	 * -e <value>
	 * --long-param
	 * --long-param=<value>
	 * --long-param <value>
	 * <value>
	 *
	 * @param array $noopt List of parameters without values
	 * @return array
	 */
	public function parseParameters($noopt = [])
	{
		$result = [];
		$params = $_SERVER['argv'] ?? [];
		// could use getopt() here (since PHP 5.3.0), but it doesn't work reliably
		reset($params);
		foreach ($params as $tmp => $p) {
			if ($p[0] === '-') {
				$pname = substr($p, 1);
				$value = true;
				if ($pname[0] === '-') {
					// long-opt (--<param>)
					$pname = substr($pname, 1);
					if (str_contains($p, '=')) {
						// value specified inline (--<param>=<value>)
						[$pname, $value] = explode('=', substr($p, 2), 2);
					}
				}
				// check if next parameter is a descriptor or a value
				$nextparm = current($params);
				if (!in_array($pname, $noopt) && $value === true && $nextparm !== false && $nextparm[0] !== '-') {
					$value = next($params);
				}
				$result[$pname] = $value;
			} else {
				// param doesn't belong to any option
				$result[] = $p;
			}
		}
		return $result;
	}

	/**
	 * http://stackoverflow.com/a/6127748/417153
	 * @return bool
	 */
	public function isRefresh()
	{
		return isset($_SERVER['HTTP_CACHE_CONTROL']) &&
			$_SERVER['HTTP_CACHE_CONTROL'] === 'max-age=0';
	}

	public function isCtrlRefresh()
	{
		return isset($_SERVER['HTTP_CACHE_CONTROL']) &&
			$_SERVER['HTTP_CACHE_CONTROL'] === 'no-cache';
	}

	public function getIntArray($name)
	{
		$array = $this->getArray($name);
		$array = array_map('intval', $array);
		return $array;
	}

	public function getFields(array $desc)
	{
		return filter_var_array($this->data, $desc);
	}

	public function clear()
	{
		$this->data = [];
	}

	/**
	 * @param int $age - seconds
	 */
	public function setCacheable($age = 60)
	{
		if (!headers_sent()) {
			header('Pragma: cache');
			header('Expires: ' . date('D, d M Y H:i:s', time() + $age) . ' GMT');
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
			header('Cache-Control: public, immutable, max-age=' . $age);
		}
	}

	public function header($name, $value)
	{
		header($name . ': ' . $value);
	}

	public function noCache()
	{
		if (!headers_sent()) {
			header('Pragma: no-cache');
			header('Expires: 0');
			header('Cache-Control: no-cache, no-store, must-revalidate');
		}
	}

	/**
	 * getNameless(1) doesn't provide validation.
	 * Use importNameless() to associate parameters 1, 2, 3, with their names
	 * @param array $keys
	 */
	public function importNameless(array $keys)
	{
		foreach ($keys as $k => $val) {
			$available = $this->getNameless($k);
			if ($available) {
				$this->data[$val] = $available;
			}
		}
	}

	public function getNameless($index, $alternative = null)
	{
		$levels = $this->getURLLevels();

		/* From DCI */
		// this spoils ORS menu!
		/*		$controller = $this->getControllerString();
				foreach ($levels as $l => $name) {
					unset($levels[$l]);
					if ($name == $controller) {
						break;
					}
				}
				$levels = array_values($levels);	// reindex
				/* } */

		if ($index < 0) {
			$index = sizeof($levels) + $index;    // negative index
		}

		return ifsetor($levels[$index])
			? urldecode($levels[$index])    // if it contains spaces
			: $this->getTrim($alternative);
	}

	/**
	 * Should work from app root
	 * When working from doc root it includes folders leading
	 * to the app root, which breaks numbers when deployed to
	 * a different server with a longer/shorter path.
	 * @return array
	 */
	public function getURLLevels()
	{
		$path = $this->getPathAfterAppRootByPath();
		//		debug($path);
		//$path = $path->getURL();
		//debug($path);
		if (strlen($path) > 1) {    // "/"
			$levels = trimExplode('/', $path);
			if ($levels && $levels[0] === 'index.php') {
				array_shift($levels);
			}
		} else {
			$levels = [];
		}
//		llog([
//			'cwd' => getcwd(),
//			//'url' => $url.'',
//			'path' => $path . '',
//			//'getURL()' => $path->getURL() . '',
//			'levels' => $levels]);
		return $levels;
	}

	public function getPathAfterAppRootByPath()
	{
		$al = AutoLoad::getInstance();
		$docRoot = clone $al->documentRoot;
		$docRoot->normalize()->realPath()->resolveLinks();

		$path = $this->url->getPath();
		$fullPath = clone $docRoot;
		$fullPath->append($path);

		//		d($docRoot.'', $path.'', $fullPath.'');
		//		exit();
		$fullPath->resolveLinksSimple();
		//		$fullPath->onlyExisting();
		//		d($fullPath.'');
		$appRoot = $al->getAppRoot()->normalize()->realPath();
		$fullPath->remove($appRoot);
		//		$path->normalize();

		return $fullPath;
	}

	public function getPOST()
	{
		return file_get_contents("php://input");
	}

	public function forceDownload($contentType, $filename)
	{
		header('Content-Type: ' . $contentType);
		header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
	}

	public function getKeys()
	{
		return array_keys($this->data);
	}

	public function getGeoLocation()
	{
		$info = $this->getGeoIP();
		return trimExplode(',', $info->loc);
	}

	public function getGeoIP()
	{
		$session = new Session(__CLASS__);
		$json = $session->get(__METHOD__);
		if (!$json) {
			$url = 'http://ipinfo.io/' . $this->getClientIP();        // 166ms
			$info = $this->fetch($url);
			if ($info) {
				$json = json_decode($info);
				$session->save(__METHOD__, $json);
			} else {
				$url = 'http://freegeoip.net/json/' . $this->getClientIP();    // 521ms
				$info = $this->fetch($url);
				if ($info) {
					$json = json_decode($info);
					$json->loc = $json->latitude . ',' . $json->longitude;    // compatibility hack
					$session->save(__METHOD__, $json);
				}
			}
		}
		return $json;
	}

	/**
	 * Returns raw data, don't use or use with care
	 * @param $key
	 * @return mixed
	 */
	public function get($key)
	{
		return ifsetor($this->data[$key]);
	}

	public function getClientIP()
	{
		$ip = ifsetor($_SERVER['REMOTE_ADDR']);
		if (!$ip || in_array($ip, [
				'127.0.0.1',
				'::1'
			])) {
			$ip = $this->fetch('http://ipecho.net/plain');
		}
		return $ip;
	}

	public function fetch($url)
	{
		if ($this->proxy) {
			$context = stream_context_create([
				'http' => [
					'proxy' => $this->proxy,
					'timeout' => 1,
				]
			]);
			$data = file_get_contents($url, null, $context);
		} else {
			$context = stream_context_create([
				'http' => [
					'timeout' => 1,
				]
			]);
			$data = file_get_contents($url, null, $context);
		}
		return $data;
	}

	public function goBack()
	{
		$ref = $this->getReferer();
		if ($ref) {
			$this->redirect($ref);
		}
		return true;
	}

	public function redirect($controller, $exit = true, array $params = [])
	{
		if (class_exists('Index')
			&& Index::getInstance()
			&& method_exists(Index::getInstance(), '__destruct')) {
			Index::getInstance()->__destruct();
		}
		if ($params) {
			$controller .= '?' . http_build_query($params);
		}
		if ($this->canRedirect($controller)) {
			if (!headers_sent()) {
				ob_start();
				debug_print_backtrace(defined('DEBUG_BACKTRACE_IGNORE_ARGS')
					? DEBUG_BACKTRACE_IGNORE_ARGS : null);
				$bt = ob_get_clean();
				$bt = trimExplode("\n", $bt);
				foreach ($bt as $i => $line) {
					$ii = str_pad($i, 2, '0', STR_PAD_LEFT);
					header('Redirect-From-' . $ii . ': ' . $line);
				}

				header('Location: ' . $controller);
			}
			echo '<meta http-equiv="refresh" content="0; url=' . $controller . '">';
			echo 'Redirecting to <a href="' . $controller . '">' . $controller . '</a>';
		} else {
			$this->redirectJS($controller, DEVELOPMENT ? 10000 : 0);
		}
		if ($exit && !self::isPHPUnit()) {
			// to preserve the session
			session_write_close();
			exit();
		}
		return $controller;
	}

	public function canRedirect($to)
	{
		if ($this->isGET()) {
			$absURL = $this->getURL();
			$absURL->makeAbsolute();
			//debug($absURL.'', $to.''); exit();
			return $absURL . '' != $to . '';
		} else {
			return true;
		}
	}

	public function isGET()
	{
		return ifsetor($_SERVER['REQUEST_METHOD'], 'GET') == 'GET';
	}

	/**
	 * Returns the current page URL as is. Similar to $_SERVER['REQUEST_URI'].
	 *
	 * @return URL
	 */
	public function getURL()
	{
		return $this->url;
	}

	public function setProxy($proxy)
	{
		$this->proxy = $proxy;
	}

	public function getZipped($string)
	{
		$base = $this->getBase64($string);
		return gzuncompress($base);
	}

	public function getBase64($string)
	{
		$base = $this->getTrim($string);
		return base64_decode($base);
	}

	public function getBrowserIP()
	{
		return $_SERVER['REMOTE_ADDR'];
	}

	public function getIDrequired()
	{
		$value = $this->getID();
		if (!$value) {
			throw new InvalidArgumentException('ID is required.');
		}
		return $value;
	}

	public function getID()
	{
		//		debug($this->getNamelessID(), $this->getInt('id'), $this->getURLLevels());
		$last = sizeof($this->getURLLevels()) - 1;
		return $this->getNamelessID()
			?: $this->getInt('id')
				?: $this->getNameless($last);
	}

	public function getNamelessID()
	{
		$nameless = $this->getURLLevels();
		foreach ($nameless as $n) {
			if (is_numeric($n)) {
				return $n;
			}
		}
		return null;
	}

	public function getHidden(array $limit = [])
	{
		$hidden = array_reduce(array_keys($this->data), function ($total, $key) {
			$item = $this->data[$key];
			if (is_array($item)) {
				$item = $this->getSubRequest($key)->getHidden();
			} else {
				$item = [
					'<input type="hidden" name="' . $key . '" value="' . $item . '" />',
				];
			}
			return array_merge($total, $item);
		}, []);
		return $hidden;
	}

	public function json(array $data)
	{
		header('Content-Type: application/json');
		$json = json_encode($data, JSON_PRETTY_PRINT);
		header('Content-Length: ' . strlen($json));
		echo $json;
		die;
	}

	public function getAction()
	{
		$action = $this->getTrim('action');
		if (!$action) {
			$action = $this->getURLLevel(1);
		}
		return $action;
	}

	public function getURLLevel($level)
	{
		$path = $this->getURLLevels();
		return $path[$level] ?? null;
	}

	public function getJsonPost()
	{
		return json_decode($this->getRawPost());
	}

	public function getRawPost()
	{
		if (defined('STDIN')) {
			$post = stream_get_contents(STDIN);
		} else {
			$post = file_get_contents('php://input');
		}
		return $post;
	}
}
