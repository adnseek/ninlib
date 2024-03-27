<?php

namespace spidgorny\nadlib\HTTP;

use AutoLoad;
use LogEntry;
use nadlib\Proxy;
use Path;
use Request;
use URLGet;

class URL
{

	/**
	 * @var string
	 */
	public $url;

	/**
	 * scheme, user, pass, host, port, path, query, fragment
	 *
	 * @var array
	 */
	public $components = [];

	/**
	 * $this->components['query'] decomposed into an array
	 * @var array
	 */
	public $params = [];

	/**
	 * @var string
	 */
	public $documentRoot = '';

	/**
	 * = $this->components['path']
	 * @var Path
	 */
	protected $path;

	/**
	 * @var array
	 */
	public $log = [];

	/**
	 * @var array
	 */
	public $cookies = [];

	public $headers = [];

	public $user_agent;

	public $cookie_file;

	public $compression;

	/**
	 * @var Proxy
	 */
	public $proxy;

	/**
	 * @param string $url - if not specified then the current page URL is reconstructed
	 * @param array $params
	 */
	public function __construct($url = null, array $params = [])
	{
		if ($url instanceof URL) {
			//return $url;	// doesn't work
//			throw new \RuntimeException(__METHOD__);
			foreach (get_object_vars($url) as $key => $val) {
				$this->$key = $val;
			}
		} elseif (empty($url)) { // empty string should not default to localhost
			$http = Request::getRequestType();
			//debug($_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'], $_SERVER);
			$host = ifsetor($_SERVER['HTTP_X_FORWARDED_HOST'], ifsetor($_SERVER['HTTP_HOST']));
			if ($host) {
				$url = $http . '://' . $host . ifsetor($_SERVER['REQUEST_URI'], '/');
			} else {
				$url = $http . '://' . (gethostname() ?: 'localhost') . '/';
			}
			$this->parseURL($url);
		} else {
			$this->parseURL($url);
		}
		if ($params) {
			$this->addParams($params);    // setParams was deleting all filters from the URL
		}
//		if (class_exists('Config')) {
//			$this->setDocumentRoot(Config::getInstance()->documentRoot);
//		}
		// infinite recursion
//		$this->setDocumentRoot(Request::getInstance()->getDocumentRoot());
		$this->setDocumentRoot(Request::getDocumentRootByRequest());
	}

	/**
	 * @param $url string
	 */
	public function parseURL($url)
	{
		$this->components = @parse_url($url);
		//pre_print_r($this->components);
		if (!$this->components) {
			// parse_url(/pizzavanti-gmbh/id:3/10.09.2012@10:30/488583b0e1f3d90d48906281f8e49253.html)
			// [function.parse-url]: Unable to parse URL
			$request = Request::getExistingInstance();
			if ($request) {
				//debug(substr($request->getLocation(), 0, -1).$url);
				$callStack = debug_backtrace();
				foreach ($callStack as &$call) {
					$call = ifsetor($call['class']) . ifsetor($call['type']) . $call['function'];
				}
				if (ifsetor($_COOKIE['d'])) {
					print_r($callStack);
					ob_end_flush();
				}
				// prevent infinite loop
				if (!in_array('Request::getLocation', $callStack) &&
					!in_array('Request->getLocation', $callStack)
				) {
					$this->components = parse_url(substr($request->getLocation(), 0, -1) . $url);
				} else {
					//debug($this->components);
				}
			}
		}
		//debug($url, $request ? 'Request::getExistingInstance' : '');
		if (isset($this->components['path'])) {
			$this->path = new Path($this->components['path']);
			// keep the original intact, just in case
//			$this->components['path'] = $this->path;
			//pre_print_r([__METHOD__, $this->components, get_class($this->path)]);
		} else {
			$this->path = new Path('/');
		}
		if (isset($this->components['query'])) {
			parse_str($this->components['query'], $this->params);
		}
	}

	public static function make(array $params = [])
	{
		$url = new self();
		$url->setParams($params);
		return $url;
	}

	/**
	 * @param $param
	 * @param $value
	 * @return static
	 */
	public function setParam($param, $value)
	{
		$this->params[$param] = $value;
		$this->components['query'] = $this->buildQuery();
		return $this;
	}

	public function unsetParam($param)
	{
		unset($this->params[$param]);
		$this->components['query'] = $this->buildQuery();
		return $this;
	}

	public function getParam($param)
	{
		return ifsetor($this->params[$param]);
	}

	/**
	 * Replaces parameters completely (with empty array?)
	 * @param array $params
	 * @return $this
	 */
	public function setParams(array $params = [])
	{
		$this->params = $params;
		$this->components['query'] = $this->buildQuery();
		return $this;
	}

	/**
	 * New params have priority
	 * @param array $params
	 * @return $this
	 */
	public function addParams(array $params = [])
	{
		$this->params = $params + $this->params;
		$this->components['query'] = $this->buildQuery();
		return $this;
	}

	public function forceParams(array $params = [])
	{
		$this->params = array_merge($this->params, $params);    // keep default order but overwrite
		$this->components['query'] = $this->buildQuery();
		return $this;
	}

	public function clearParams()
	{
		$this->setParams([]);
		return $this;
	}

	public function appendParams(array $params)
	{
		$this->params += $params;
		$this->components['query'] = $this->buildQuery();
	}

	public function __clone()
	{
		$this->path = clone $this->path;
	}

	/**
	 * @return Path
	 */
	public function getPath()
	{
		$path = $this->path;
		if (!$path instanceof \Path) {
			debug(gettype($path), get_class($path), get_object_vars($path));
			debug_pre_print_backtrace();
		}
		assert($path instanceof \Path);
		if ($this->documentRoot !== '/') {
			//$path = str_replace($this->documentRoot, '', $path);	// WHY???
		}
		if (!$path instanceof Path) {
			$this->path = new Path($path);
		}
		nodebug([
			'class($this->path)' => get_class($this->path),
			'$this->path' => $this->path . '',
			'documentRoot' => $this->documentRoot . '',
			'class($path)' => get_class($path),
			'path' => $path . '',
		]);
		return $this->path;
	}

	/**
	 * @param string|Path $path
	 * @return $this
	 */
	public function setPath($path)
	{
		$this->components['path'] = $path instanceof Path ? $path : new Path($path);
		$this->path = $this->components['path'];
		return $this;
	}

	public function reset()
	{
		$this->components['path'] = $this->documentRoot;
		$this->components['query'] = '';
		$this->clearParams();
	}

	/**
	 * Defines the filename in the URL
	 * @param $name
	 * @return $this
	 */
	public function setBasename($name)
	{
		$this->path->setFile($name);
		return $this;
	}

	public function getBasename()
	{
		return basename($this->getPath());
	}

	public function getExtension()
	{
		$basename = $this->getBasename();
		$ext = pathinfo($basename, PATHINFO_EXTENSION);
		return $ext;
	}

	/**
	 * Lowercase guaranteed
	 * @return mixed|string
	 */
	public function getExtensionLC()
	{
		$ext = $this->getExtension();
		$ext = mb_strtolower($ext);
		return $ext;
	}

	public function setDocumentRoot($root)
	{
		$this->documentRoot = $root;
		//debug($this);
		return $this;
	}

	public function setFragment($name)
	{
		if ($name[0] == '#') {
			$name = substr($name, 1);
		}
		$this->components['fragment'] = $name;
		return $this;
	}

	public function buildQuery()
	{
		$queryString = http_build_query($this->params, '_');
		$queryString = str_replace('#', '%23', $queryString);
		//parse_str($queryString, $queryStringTest);
		//debug($this->params, $queryStringTest);
		return $queryString;
	}

	/**
	 * http://de2.php.net/manual/en/function.parse-url.php#85963
	 *
	 * @param null $parsed
	 * @return string
	 */
	public function buildURL($parsed = null)
	{
		if (!$parsed) {
			// to make sure manual manipulations are not possible (although it's already protected?)
			$this->components['query'] = $this->buildQuery();
			$parsed = $this->components;
		}
		if (!is_array($parsed)) {
			return false;
		}

		$uri = isset($parsed['scheme'])
			? $parsed['scheme'] . ':' . ((strtolower($parsed['scheme']) == 'mailto') ? '' : '//')
			: '';
		$uri .= isset($parsed['user'])
			? $parsed['user'] . (isset($parsed['pass']) ? ':' . $parsed['pass'] : '') . '@'
			: '';

		$uri .= isset($parsed['host']) ? $parsed['host'] : '';
		$uri .= isset($parsed['port']) ? ':' . $parsed['port'] : '';

		if (isset($parsed['path'])) {
			$uri .= (substr($parsed['path'], 0, 1) == '/') ?
				$parsed['path'] : ((!empty($uri) ? '/' : '') . $parsed['path']);
		}

		$uri .= /*isset*/
			($parsed['query']) ? '?' . $parsed['query'] : '';
		$uri .= isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

		return $uri;
	}

	public function __toString()
	{
		if (ifsetor($this->components['host'])) {
			$url = $this->buildURL();
		} else {
			$url = '';
//			if (ifsetor($this->components['path'])
//				&& $this->components['path'] != '/') {
//				$url .= $this->components['path'];
//			}
			$url .= $this->path . '';
			if (ifsetor($this->components['query'])) {
				$url .= '?' . $this->components['query'];
			}
			if (ifsetor($this->components['fragment'])) {
				$url .= '#' . $this->components['fragment'];
			}
		}
		//debug($this->components, $url);
		return $url . '';
	}

	public function getRequest()
	{
		$r = new Request($this->params ? $this->params : []);
		$r->url = $this;
		return $r;
	}

	/**
	 * @static
	 * @return URL
	 */
	public static function getCurrent()
	{
		return new URL();
	}

	public function GET()
	{
		return file_get_contents($this->buildURL());
	}

	public function POST($login = null, $password = null)
	{
		$auth = null;
		if ($login) {
			$auth = "Authorization: Basic " . base64_encode($login . ':' . $password) . PHP_EOL;
		}
		$stream = [
			'http' => [
				'method' => 'POST',
				'header' => 'Content-Type: application/x-www-form-urlencoded' . PHP_EOL . $auth,
				'content' => $this->components['query'],
			],
		];
		$context = stream_context_create($stream);

		$noQuery = $this->components;
		unset($noQuery['query']);
		$url = $this->buildURL($noQuery);
		return file_get_contents($url, false, $context);
	}

	public function getCURL()
	{
		$process = curl_init($this->__toString());
		curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
		curl_setopt($process, CURLOPT_HEADER, 1);
		curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);
		if ($this->cookies == true) {
			curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
		}
		if ($this->cookies == true) {
			curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
		}
		curl_setopt($process, CURLOPT_ENCODING, $this->compression);
		curl_setopt($process, CURLOPT_TIMEOUT, 30);
		if ($this->proxy) {
			curl_setopt($process, CURLOPT_PROXY, $this->proxy);
		}
		curl_setopt($process, CURLOPT_POSTFIELDS, $this->buildQuery());
		curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($process, CURLOPT_POST, 1);
		return $process;
	}

	public function CURL()
	{
		$process = $this->getCURL();
		$return = curl_exec($process);
		curl_close($process);
		return $return;
	}

	public function getURLGet()
	{
		return new URLGet($this->__toString());
	}

	public function exists()
	{
		$AgetHeaders = @get_headers($this->buildURL());
		return preg_match("|200|", $AgetHeaders[0]);
	}

	/**
	 * Works well when both paths are absolute.
	 * Comparing server path to URL path does not work.
	 * http://stackoverflow.com/a/2638272/417153
	 * @param string $from
	 * @param string $to
	 * @return string
	 */
	public static function getRelativePath($from, $to)
	{
		0 && debug(
			$_SERVER['DOCUMENT_ROOT'],
			$from,
			$to,
			__FILE__,
			trimExplode(':', ini_get('open_basedir')),
			$_SERVER
		);
		//		exit;
		// some compatibility fixes for Windows paths
		$from = self::getPathFolders($from);
		$to = self::getPathFolders($to);
		$relPath = $to;

		foreach ($from as $depth => $dir) {
			// find first non-matching dir
			//debug($depth, $dir, $to[$depth]);
			if (isset($to[$depth]) && $dir === $to[$depth]) {
				// ignore this directory
				array_shift($relPath);
			} else {
				// get number of remaining dirs to $from
				$remaining = count($from) - $depth;
				if ($remaining > 1) {
					// add traversals up to first matching dir
					$padLength = (count($relPath) + $remaining - 1) * -1;
					$relPath = array_pad($relPath, $padLength, '..');
					break;
				} elseif (is_array($relPath) && isset($relPath[0])) {
					$relPath[0] = './' . $relPath[0];
				}
			}
		}
		//debug($from, $to, $relPath);
		return implode('/', $relPath);
	}

	public static function getScriptWithPath()
	{
		//if ($_SERVER['SCRIPT_FILENAME']{0} != '/') {
		// Pedram: we have to use __FILE__ constant in order to be able to execute phpUnit tests within PHPStorm
		// C:\Users\DEJOKMAJ\AppData\Local\Temp\ide-phpunit.php
		if (Request::isCLI()) {
			$scriptWithPath = isset($_SERVER['SCRIPT_FILENAME'])
				? $_SERVER['SCRIPT_FILENAME']
				: $_SERVER['PHP_SELF']; // can be relative!!!

			//debug($scriptWithPath);
			// this below may not work since __FILE__ is class.URL.php and not index.php
			// but this our last chance for CLI/Cron
			if (!$scriptWithPath || !Path::isItAbsolute($scriptWithPath)) {    // relative not OK
				if (basename(__FILE__) == __FILE__) {    // index.php
					$scriptWithPath = getcwd() . '/' . __FILE__;
				} else {
					$scriptWithPath = __FILE__;
				}
			}
		} else {
			$scriptWithPath = $_SERVER['SCRIPT_FILENAME'];
			$scriptWithPath = str_replace('/kunden', '', $scriptWithPath); // 1und1.de

			// add /data001/ to /data001/srv/www/htdocs
			// in virtual environments (symlink)
			$scriptWithPath = realpath($scriptWithPath);
		}
		return $scriptWithPath;
	}

	/**
	 * @return string
	 */
	public function getDomain()
	{
		return $this->components['host'];
	}

	/**
	 * "asd/qwe\zxc/" => ['asd', 'qwe', 'zxc']
	 * Takes care of Windows path and removes empty
	 * @param $from
	 * @return array
	 */
	public static function getPathFolders($from)
	{
		//		ob_start();
		//		debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		if (!ini_get('open_basedir')) {
			$from = is_dir($from) ? rtrim($from, '\/') . '/' : $from;
		}
		$from = str_replace('\\', '/', $from);
		$from = explode('/', $from);
		$from = array_filter($from);
		return $from;
	}

	/**
	 * @param string $path1
	 * @param string $path2
	 * @return array
	 */
	public static function getCommonRoot($path1, $path2)
	{
		$path1 = self::getPathFolders($path1);
		$path2 = self::getPathFolders($path2);
		$common = array_intersect($path1, $path2);
		//debug($path1, $path2, $common);
		return $common;
	}

	/**
	 * http://www.php.net/manual/en/function.realpath.php#71334
	 * @param $address
	 * @return array|mixed|string
	 */
	public function canonicalize($address)
	{
		$address = explode('/', $address);
		$keys = array_keys($address, '..');

		foreach ($keys as $keypos => $key) {
			array_splice($address, $key - ($keypos * 2 + 1), 2);
		}

		$address = implode('/', $address);
		$address = str_replace('./', '', $address);
		return $address;
	}

	protected function log($action, $data = null)
	{
		$this->log[] = new LogEntry($action, $data);
	}

	public function resolve($relativeURL)
	{
		return $this->url_to_absolute($this->__toString(), $relativeURL);
	}

	/**
	 * http://nadeausoftware.com/articles/2008/05/php_tip_how_convert_relative_url_absolute_url
	 * @param $baseUrl
	 * @param $relativeUrl
	 * @return mixed
	 */
	private function url_to_absolute($baseUrl, $relativeUrl)
	{
		// If relative URL has a scheme, clean path and return.
		$r = $this->split_url($relativeUrl);
		if ($r === false) {
			$this->log('Unable to split', $relativeUrl);
			return false;
		}
		if (!empty($r['scheme'])) {
			if (!empty($r['path']) && $r['path'][0] == '/') {
				$r['path'] = $this->url_remove_dot_segments($r['path']);
			}
			return $this->join_url($r);
		}

		// Make sure the base URL is absolute.
		$b = $this->split_url($baseUrl);
		if ($b === false || empty($b['scheme']) || empty($b['host'])) {
			$this->log('unable to split', $baseUrl);
			return false;
		}
		$r['scheme'] = $b['scheme'];

		// If relative URL has an authority, clean path and return.
		if (isset($r['host'])) {
			if (!empty($r['path'])) {
				$r['path'] = $this->url_remove_dot_segments($r['path']);
			}
			return $this->join_url($r);
		}
		unset($r['port']);
		unset($r['user']);
		unset($r['pass']);

		// Copy base authority.
		$r['host'] = $b['host'];
		if (isset($b['port'])) {
			$r['port'] = $b['port'];
		}
		if (isset($b['user'])) {
			$r['user'] = $b['user'];
		}
		if (isset($b['pass'])) {
			$r['pass'] = $b['pass'];
		}

		// If relative URL has no path, use base path
		if (empty($r['path'])) {
			if (!empty($b['path'])) {
				$r['path'] = $b['path'];
			}
			if (!isset($r['query']) && isset($b['query'])) {
				$r['query'] = $b['query'];
			}
			return $this->join_url($r);
		}

		//debug($relativeUrl, $relativeUrl.'', $r);
		// If relative URL path doesn't start with /, merge with base path
		if ($r['path'][0] != '/') {
			$base = mb_strrchr($b['path'], '/', true, 'UTF-8');
			if ($base === false) {
				$base = '';
			}
			$r['path'] = $base . '/' . $r['path'];
		}
		$r['path'] = $this->url_remove_dot_segments($r['path']);
		return $this->join_url($r);
	}

	public function url_remove_dot_segments($path)
	{
		// multi-byte character explode
		$inSegs = preg_split('!/!u', $path);
		$outSegs = [];
		foreach ($inSegs as $seg) {
			if ($seg == '' || $seg == '.') {
				continue;
			}
			if ($seg == '..') {
				array_pop($outSegs);
			} else {
				array_push($outSegs, $seg);
			}
		}
		$outPath = implode('/', $outSegs);
		if ($path[0] == '/') {
			$outPath = '/' . $outPath;
		}
		// compare last multi-byte character against '/'
		if ($outPath != '/' && (mb_strlen($path) - 1) == mb_strrpos($path, '/', 'UTF-8')
		) {
			$outPath .= '/';
		}
		return $outPath;
	}

	/**
	 * PHP's standard parse_url( ) looks useful. It splits apart a URL and returns an associative array containing the
	 * scheme, host, path, and so on. It works well on simple URLs like "http://example.com/index.htm". However, it has
	 * problems parsing complex URLs, like "http://example.com/redirect?url=http://elsewhere.com". It is confused by
	 * some relative URLs, such as "//example.com/index.htm". And it doesn't properly handle URLs using IPv6 addresses.
	 * The parser also is not as strict as it should be and will allow illegal characters and invalid URL structure.
	 * This makes it hard to use parse_url( ) reliably for validating links in link checkers and other tools.
	 * http://nadeausoftware.com/articles/2008/05/php_tip_how_parse_and_build_urls
	 * @param      $url
	 * @param bool $decode
	 * @return mixed
	 */
	public function split_url($url, $decode = true)
	{
		$parts = [];
		$xunressub = 'a-zA-Z\d\-._~\!$&\'()*+,;=';
		$xpchar = $xunressub . ':@%';

		$xscheme = '([a-zA-Z][a-zA-Z\d+-.]*)';

		$xuserinfo = '(([' . $xunressub . '%]*)' .
			'(:([' . $xunressub . ':%]*))?)';

		$xipv4 = '(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})';

		$xipv6 = '(\[([a-fA-F\d.:]+)\])';

		$xhost_name = '([a-zA-Z\d\-.%]+)';

		$xhost = '(' . $xhost_name . '|' . $xipv4 . '|' . $xipv6 . ')';
		$xport = '(\d*)';
		$xauthority = '((' . $xuserinfo . '@)?' . $xhost .
			'?(:' . $xport . ')?)';

		$xslash_seg = '(\/[' . $xpchar . ']*)';
		$xpath_authabs = '((\/\/' . $xauthority . ')((\/[' . $xpchar . ']*)*))';
		$xpath_rel = '([' . $xpchar . ']+' . $xslash_seg . '*)';
		$xpath_abs = '(\/(' . $xpath_rel . ')?)';
		$xapath = '(' . $xpath_authabs . '|' . $xpath_abs .
			'|' . $xpath_rel . ')';

		$xqueryfrag = '([' . $xpchar . '\/?' . ']*)';

		$xurl = '^(' . $xscheme . ':)?' . $xapath . '?' .
			'(\?' . $xqueryfrag . ')?(#' . $xqueryfrag . ')?$';


		// Split the URL into components.
		if (!preg_match('!' . $xurl . '!', $url, $m)) {
			return false;
		}

		if (!empty($m[2])) {
			$parts['scheme'] = strtolower($m[2]);
		}

		if (!empty($m[7])) {
			if (isset($m[9])) {
				$parts['user'] = $m[9];
			} else {
				$parts['user'] = '';
			}
		}
		if (!empty($m[10])) {
			$parts['pass'] = $m[11];
		}

		if (!empty($m[13])) {
			$h = $parts['host'] = $m[13];
		} elseif (!empty($m[14])) {
			$parts['host'] = $m[14];
		} elseif (!empty($m[16])) {
			$parts['host'] = $m[16];
		} elseif (!empty($m[5])) {
			$parts['host'] = '';
		}
		if (!empty($m[17])) {
			$parts['port'] = $m[18];
		}

		if (!empty($m[19])) {
			$parts['path'] = $m[19];
		} elseif (!empty($m[21])) {
			$parts['path'] = $m[21];
		} elseif (!empty($m[25])) {
			$parts['path'] = $m[25];
		}

		if (!empty($m[27])) {
			$parts['query'] = $m[28];
		}
		if (!empty($m[29])) {
			$parts['fragment'] = $m[30];
		}

		if (!$decode) {
			return $parts;
		}
		if (!empty($parts['user'])) {
			$parts['user'] = rawurldecode($parts['user']);
		}
		if (!empty($parts['pass'])) {
			$parts['pass'] = rawurldecode($parts['pass']);
		}
		if (!empty($parts['path'])) {
			$parts['path'] = rawurldecode($parts['path']);
		}
		if (isset($h)) {
			$parts['host'] = rawurldecode($parts['host']);
		}
		if (!empty($parts['query'])) {
			$parts['query'] = rawurldecode($parts['query']);
		}
		if (!empty($parts['fragment'])) {
			$parts['fragment'] = rawurldecode($parts['fragment']);
		}
		return $parts;
	}

	public function join_url($parts, $encode = true)
	{
		if ($encode) {
			if (isset($parts['user'])) {
				$parts['user'] = rawurlencode($parts['user']);
			}
			if (isset($parts['pass'])) {
				$parts['pass'] = rawurlencode($parts['pass']);
			}
			if (isset($parts['host']) &&
				!preg_match('!^(\[[\da-f.:]+\]])|([\da-f.:]+)$!ui', $parts['host'])
			) {
				$parts['host'] = rawurlencode($parts['host']);
			}
			if (!empty($parts['path'])) {
				$parts['path'] = preg_replace(
					'!%2F!ui',
					'/',
					rawurlencode($parts['path'])
				);
			}
			if (isset($parts['query'])) {
				$parts['query'] = rawurlencode($parts['query']);
			}
			if (isset($parts['fragment'])) {
				$parts['fragment'] = rawurlencode($parts['fragment']);
			}
		}

		$url = '';
		if (!empty($parts['scheme'])) {
			$url .= $parts['scheme'] . ':';
		}
		if (isset($parts['host'])) {
			$url .= '//';
			if (isset($parts['user'])) {
				$url .= $parts['user'];
				if (isset($parts['pass'])) {
					$url .= ':' . $parts['pass'];
				}
				$url .= '@';
			}
			if (preg_match('!^[\da-f]*:[\da-f.:]+$!ui', $parts['host'])) {
				$url .= '[' . $parts['host'] . ']';
			} // IPv6
			else {
				$url .= $parts['host'];
			}             // IPv4 or name
			if (isset($parts['port'])) {
				$url .= ':' . $parts['port'];
			}
			if (!empty($parts['path']) && $parts['path'][0] != '/') {
				$url .= '/';
			}
		}
		if (!empty($parts['path'])) {
			$url .= $parts['path'];
		}
		if (isset($parts['query'])) {
			$url .= '?' . $parts['query'];
		}
		if (isset($parts['fragment'])) {
			$url .= '#' . $parts['fragment'];
		}
		return $url;
	}

	public function setRelativePath($pathPlus)
	{
		$newPath = $this->url_to_absolute($this->__toString(), $pathPlus);
		//debug($this->__toString(), $pathPlus, $newPath);
		$this->parseURL($newPath);
	}

	/**
	 * @param string $string - source page name
	 * @param bool $preserveSpaces - leaves spaces
	 * @return string                - converted to URL friendly name
	 */
	public static function friendlyURL($string, $preserveSpaces = false)
	{
		$string = preg_replace("`\[.*\]`U", "", $string);
		$string = preg_replace('`&(amp;)?#?[a-z0-9]+;`i', '-', $string);
		$string = htmlentities($string, ENT_COMPAT, 'utf-8');
		$string = preg_replace("`&([a-z])(acute|uml|circ|grave|ring|cedil|slash|tilde|caron|lig|quot|rsquo);`i", "\\1", $string);
		if (!$preserveSpaces) {
			$string = preg_replace(["`[^a-z0-9]`i", "`[-]+`"], "-", $string);
		}
		return strtolower(trim($string, '-'));
	}

	public static function getSlug($string)
	{
		$string = mb_strtolower($string);
		$string = preg_replace("` +`", "-", $string);
		$string = str_replace('/', '-', $string);
		$string = str_replace('\\', '-', $string);
		$string = str_replace('"', '-', $string);
		$string = str_replace("'", '-', $string);
		$string = trim($string);
		return $string;
	}

	public function makeAbsolute()
	{
		if (!ifsetor($this->components['scheme'])) {
			$this->components['scheme'] = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on'
				? 'https'
				: 'http';
		}
		if (!ifsetor($this->components['host'])) {
			$this->components['host'] = $_SERVER['HTTP_HOST'];
		}
		if (!ifsetor($this->components['path'])) {
			$this->components['path'] = $_SERVER['REQUEST_URI'];
		}
		return $this;
	}

	public function getScheme()
	{
		return ifsetor($this->components['scheme']);
	}

	public function getHost()
	{
		return ifsetor($this->components['host']);
	}

	public function setHost($host)
	{
		$this->components['host'] = $host;
	}

	public function getPort()
	{
		return ifsetor($this->components['port']);
	}

	public function getUser()
	{
		return ifsetor($this->components['user']);
	}

	public function getPass()
	{
		return ifsetor($this->components['pass']);
	}

	public function getHash()
	{
		return ifsetor($this->components['fragment']);
	}

	/**
	 * @param array $queryString - array of objects with (->name, ->value)
	 */
	public function setParamsFromHAR($queryString)
	{
		foreach ($queryString as $pair) {
			$this->setParam($pair->name, $pair->value);
		}
	}

	public function replaceController($newController)
	{
		if (is_array($newController)) {
			$newController = implode('/', $newController);
		}
		$path = $this->getPath();
		$diff = '';
		if ($this->documentRoot != '/') {
			$diff = str_replace($this->documentRoot, '', $path);
		}
		debug([
			'original' => $path . '',
			'docroot' => $this->documentRoot,
			'diff' => $diff,
			'replace-by' => $newController,
		]);
		$path = str_replace($diff, '/' . $newController, $path);
		$this->setPath($path);
		return $this;
	}

	public function getParams()
	{
		return $this->params;
	}

	public function makeRelative()
	{
		$al = AutoLoad::getInstance();
		$path = $this->getPath();
		//		debug($path.'', $path->isAbsolute(), $al->getAppRoot().'');
		if ($path->isAbsolute() && $path->exists()) {
			$this->setPath($path->relativeFromAppRoot());
		} else {
			$new = array_diff($path->aPath, $al->getAppRoot()->aPath);
			$this->setPath(new Path(implode('/', $new)));
		}
		return $this;
	}
}
