<?php

use Bavix\AdvancedHtmlDom\AdvancedHtmlDom;
use Michelf\Markdown;
use spidgorny\nadlib\HTTP\URL;

class View extends stdClass implements ToStringable
{

	//use HTMLHelper;
	//use ViewPHP7;

	/**
	 * @var stdClass
	 */
	public $caller;
	/**
	 * @var Index
	 */
	public $index;
	/**
	 * Store something here and then @use $this->data('asd') to access it with escaping
	 * @var array
	 */
	public $data = [];
	/**
	 * @var AppController
	 */
	public $controller;
	public $processed;
	/**
	 * @var string
	 */
	protected $file;
	/**
	 * @var LocalLang
	 */
	protected $ll;
	/**
	 * @var Request
	 */
	protected $request;
	protected $parts = [];
	protected $folder;

	public function __construct($file, $copyObject = null)
	{
		TaylorProfiler::start(__METHOD__ . ' (' . $file . ')');
		$config = class_exists('Config')
			? Config::getInstance() : new stdClass();
		$appRoot = AutoLoad::getInstance()->getAppRoot();
		$this->folder = (ifsetor($appRoot) ? cap($appRoot, '/') : '')
			. 'template/';
		if (class_exists('Config') && ifsetor($config->config[__CLASS__]['folder'])) {
			$this->folder = dirname(__FILE__) . '/' . $config->config[__CLASS__]['folder'];
		}
		$this->file = $file;
		if (!is_readable($this->folder . $this->file)) {
			//debug(filesize($this->folder.$this->file));
			//throw new Exception('File not readable '.$this->file);
		}
		/*nodebug(
			$config->appRoot,
			$config->config[__CLASS__],
			$config->config[__CLASS__]['folder'],
			$this->folder,
			$this->file);*/
		if (is_object($copyObject)) {
			$this->caller = $copyObject;
		} elseif (is_array($copyObject)) {
			$this->caller = (object)$copyObject;
		}
		$this->ll = (class_exists('Config') && Config::getInstance()->getLL())
			? Config::getInstance()->getLL() : null;
		$this->request = Request::getInstance();
		$this->index = class_exists('Index')
			? Index::getInstance() : null;
		TaylorProfiler::stop(__METHOD__ . ' (' . $file . ')');
	}

	public static function getInstance($file, $copyObject = null)
	{
		return new self($file, $copyObject);
	}

	/*	Add as many public properties as you like and use them in the PHTML file. */

	public static function bar($percent, array $params = [], $attr = [])
	{
		$percent = round($percent);
		$src = AutoLoad::getInstance()->nadlibFromDocRoot . 'bar.php?' . http_build_query($params + [
					'rating' => $percent,
					'color' => '6DC5B4',
				]);
		$attr += [
			'src' => $src,
			'alt' => $percent . '%',
		];
		return new HTMLTag('img', $attr, null);
	}

	public static function markdown($text)
	{
		return Markdown::defaultTransform($text);
	}

	/**
	 * Really primitive and buggy.
	 * @use markdown() instead
	 * @param string $text
	 * @param callable $linkCallback
	 * @return mixed|string
	 */
	public function wikify($text, $linkCallback = null)
	{
		$inUL = false;
		$lines2 = [];
		$lines = trimExplode("\n", '' . $text);
		foreach ($lines as $line) {
			if ($line[0] === '*' || $line[0] === '-') {
				if (!$inUL) {
					$lines2[] = '<ul>';
					$inUL = true;
				}
			}
			$lines2[] = $inUL
				? '<li>' . substr($line, 2) . '</li>'
				: $line;
			if ($line[0] !== '*' && $line[0] !== '-') {
				if ($inUL) {
					$lines2[] = '</ul>';
					$inUL = false;
				}
			}
		}
		if ($inUL) {
			$lines2[] = '</ul>';
		}
		$text = implode("\n", $lines2);
		//debug($lines2, $text);
		//$text = str_replace("\n* ", "\n<li> ", $text);
		//$text = str_replace("\n- ", "\n<li> ", $text);
		$text = str_replace("\n<ul>\n", '<ul>', $text);
		$text = str_replace("</ul>\n", '</ul>', $text);
		$text = str_replace("\n\n", "</p>\n<p>", $text);
		$text = str_replace('<p></p>', '', $text);
		$text = str_replace('<p></p>', '', $text);
		if ($linkCallback) {
			$text = preg_replace_callback('/\[\[(.*?)\]\]/', $linkCallback, $text);
		}
		$text = preg_replace('/====(.*?)====/', '<h2>\1</h2>', $text);
		return $text;
	}

	/**
	 * Will load the template file and split it by the divisor.
	 * Use renderPart($i) to render the corresponding part.
	 *
	 * @param string $sep
	 */
	public function splitBy($sep)
	{
		$file = $this->getFile();
		$content = file_get_contents($file);
		$this->parts = explode($sep, $content);
	}

	public function getFile()
	{
		$path = new Path($this->file);
//		debug($path, $path->isAbsolute());
		//debug(dirname($this->file), $this->folder, $this->file, $file, filesize($file));
		return $path->isAbsolute()
			? $this->file
			: $this->folder . $this->file;
	}

	/**
	 * http://www.php.net/manual/en/function.eval.php#88820
	 *
	 * @param int $i
	 * @return string
	 */
	public function renderPart($i)
	{
		//debug($this->parts[$i]);
		return eval('?>' . $this->parts[$i]);
	}

	public function data($key)
	{
		if ($this->caller != null) {
			return $this->e($this->caller->get($key));
		}
		return null;
	}

	public function e($str)
	{
		return $this->escape($str);
	}

	/**
	 * Uses htmlspecialchars()
	 * @param string $str
	 * @return string
	 */
	public function escape($str)
	{
		return htmlspecialchars($str, ENT_QUOTES);
	}

	/**
	 * Use this helper to make URL (makeURL, getURL)
	 * @param array $params
	 * @return URL
	 */
	public function link(array $params)
	{
		return $this->getController()->makeURL($params);
	}

	public function getController()
	{
		if (!$this->controller) {
			$this->controller = Index::getInstance()->getController();
		}
		return $this->controller;
	}

	public function ahref($text, $href)
	{
		return new HTMLTag('a', [
			'href' => $href,
		], $text);
	}

	public function __call($func, array $args)
	{
		$method = [$this->caller, $func];
		if (!is_callable($method) || !method_exists($this->caller, $func)) {
			//$method = array($this->caller, end(explode('::', $func)));
			$methodName = is_object($this->caller) ? get_class($this->caller) . '::' . $func : $func;
			throw new RuntimeException('View: Method ' . $func . ' (' . $methodName . ') doesn\'t exists.');
		}
		return call_user_func_array($method, $args);
	}

	public function &__get($var)
	{
		if ($this->caller !== null) {
			return $this->caller->$var;
		}
	}

	public function __set($var, $val)
	{
		if (!$this->caller) {
			$this->caller = new stdClass();
		}
		$this->caller->$var = &$val;
	}

	public function __isset($name)
	{
		if ($this->caller !== null) {
			return $this->caller->$name;
		}
	}

	/**
	 * NAME        : autolink()
	 * VERSION     : 1.0
	 * AUTHOR      : J de Silva
	 * DESCRIPTION : returns VOID; handles converting
	 * URLs into clickable links off a string.
	 * TYPE        : functions
	 * http://www.gidforums.com/t-1816.html
	 * ======================================*/

	public function autolink(&$text, $target = '_blank', $nofollow = true)
	{
		// grab anything that looks like a URL...
		$urls = $this->_autolink_find_URLS($text);
		if (!empty($urls)) {
			// i.e. there were some URLS found in the text
			array_walk($urls, [$this, '_autolink_create_html_tags'], [
				'target' => $target,
				'nofollow' => $nofollow,
			]);
			$text = str_replace(array_keys($urls), array_values($urls), $text);
		}
		return $text;
	}

	public static function _autolink_find_URLS($text)
	{
		// build the patterns
		$scheme = '(http:\/\/|https:\/\/)';
		$www = 'www\.';
		$ip = '\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}';
		$subdomain = '[-a-z0-9_]+\.';
		$name = '[a-z][-a-z0-9]+\.';
		$tld = '[a-z]+(\.[a-z]{2,2})?';
		$the_rest = '\/?[a-z0-9._\/~#&=;%+?-]+[a-z0-9\/#=?]{1,1}';
		$pattern = "$scheme?(?(1)($ip|($subdomain)?$name$tld)|($www$name$tld))$the_rest";

		$pattern = '/' . $pattern . '/is';
		$c = preg_match_all($pattern, $text, $m);
		unset($text, $scheme, $www, $ip, $subdomain, $name, $tld, $the_rest, $pattern);
		if ($c) {
			return (array_flip($m[0]));
		}
		return ([]);
	}

	public function _autolink_create_html_tags(&$value, $key, $other = null)
	{
		$target = $nofollow = null;
		if (is_array($other)) {
			$target = ($other['target'] ? " target=\"$other[target]\"" : null);
			// see: http://www.google.com/googleblog/2005/01/preventing-comment-spam.html
			$nofollow = ($other['nofollow'] ? ' rel="nofollow"' : null);
		}
		$value = "<a href=\"$key\"$target$nofollow>$key</a>";
	}

	public function linkBIDs($text)
	{
		$text = preg_replace('/\[#(\d+)\]/', '<a href="?bid=$1">$1</a>', $text);
		return $text;
	}

	public function euro($val, $noCent = false)
	{
		$money = $this->money($val) . '&nbsp;&euro;';
		if ($noCent) {
			$money = str_replace('.00', '.-', $money);
		}
		return $money;
	}

	public function money($val)
	{
		return number_format(floatval($val), 2, '.', '');
	}

	public function purifyLinkify($comment)
	{
		$comment = preg_replace("/#(\w+)/", "<a href=\"Search?q=\\1\" target=\"_blank\">#\\1</a>", $comment);
		$comment = $this->cleanComment($comment);
		$comment = nl2br($comment);
		$comment .= $this->getEmbeddables($comment);
		return $comment;
	}

	/**
	 * @param string $comment
	 * @return string
	 */
	public function cleanComment($comment, array $allowedTags = [
		'a[href]'
	])
	{
		//$v = new View('');
		//$comment = $v->autolink($comment);
		$config = HTMLPurifier_Config::createDefault();
		//debug($config);
		$config->set('HTML.Allowed', implode(',', $allowedTags));
		$config->set('Attr.AllowedFrameTargets', ['_blank']);
		$config->set('Attr.AllowedRel', ['nofollow']);
		$config->set('AutoFormat.Linkify', true);
		$config->set('HTML.TargetBlank', true);
		$config->set('HTML.Nofollow', true);
		$purifier = new HTMLPurifier($config);
		return $purifier->purify($comment);
	}

	public function getEmbeddables($comment)
	{
		$content = '';
		$links = $this->getLinks($comment);
		foreach ($links as $link => $_) {
			$Essence = Essence\Essence::instance();
			$Media = $Essence->extract($link);

			if ($Media) {
				$content .= $Media->html;
			}
		}
		return $content;
	}

	/**
	 * @param $comment
	 * @return array
	 */
	public function getLinks($comment)
	{
		return self::_autolink_find_URLS($comment);
	}

	/**
	 * @param array $some
	 */
	public function setSome(array $some)
	{
		foreach ($some as $key => $val) {
			$this->key = $val;
		}
	}

	public function replace(array $map)
	{
		$file = $this->getFile();
		$content = $this->getContent($file);
		return str_replace(
			array_keys($map),
			array_values($map),
			$content
		);
	}

	public function getContent($file, array $variables = [])
	{
		ob_start();

		extract($variables, EXTR_OVERWRITE);

		//debug($file);
		/** @noinspection PhpIncludeInspection */
		$content = require($file);

		if (!$content || $content === 1) {
			$content = ob_get_clean();
		} else {
			ob_end_clean();
		}

		return $this->s($content);
	}

	public function s($a)
	{
//		echo typ($a), BR;
//		debug_pre_print_backtrace();
//		echo '<hr />';
		return MergedContent::mergeStringArrayRecursive($a);
	}

	public function curly()
	{
		$file = $this->getFile();
		$template = $this->getContent($file);
		preg_match_all('/\{([^}]+)\}/m', $template, $matches);
//		debug($matches);
		foreach ($matches[1] as $i => $m) {
			$val = eval(' return ' . $m . ';');
			$template = str_replace('{' . $m . '}', $val, $template);
		}
		return $template;
	}

	public function setHTML($html)
	{
		$this->processed = $html;
	}

	public function withoutScripts()
	{
		$scripts = $this->extractScripts();
		$this->index->footer[basename($this->file)] = $scripts;
		return $this;
	}

	/**
	 * composer require hrmatching/advanced_html_dom
	 */
	public function extractScripts()
	{
		$html = $this->render();
		$dom = new AdvancedHtmlDom($html);
		$scripts = $dom->find('script');
		$scripts->remove();
		$this->processed = $dom->body->innerhtml();
		return $scripts->__toString();
	}

	public function render(array $variables = [])
	{
		$key = __METHOD__ . ' (' . basename($this->file) . ')';
		TaylorProfiler::start($key);

		if (!$this->processed) {
			$file = $this->getFile();
			$content = $this->getContent($file, $variables);

			// Locallang replacement
			$content = $this->localize($content);

			if (DEVELOPMENT) {
				// not allowed in MRBS as some templates return OBJECT(!)
//				$content = '<div style="border: solid 1px red;">'.$file.'<br />'.$content.'</div>';
				$content .= '<!-- View template: ' . $this->file . ' -->' . "\n";
			}
			$this->processed = $content;
		}
		TaylorProfiler::stop($key);
		return $this->processed;
	}

	public function localize($content)
	{
		preg_match_all('/__([^ _\n\r]+?)__/', $content, $matches1);
		preg_match_all('/__\{([^\n\r}]+?)\}__/', $content, $matches2);
//		debug($matches1, $matches2); die;
		$localizeList = array_merge($matches1[1], $matches2[1]);
		foreach ($localizeList as $ll) {
			if ($ll) {
				//debug('__' . $ll . '__', __($ll));
				$content = str_replace('__' . $ll . '__', __($ll), $content);
				$content = str_replace('__{' . $ll . '}__', __($ll), $content);
			}
		}
		return $content;
	}

	/**
	 * Using this often leads to error
	 * Method View::__toString() must not throw an exception
	 * which prevents seeing the trace of where the problem happened.
	 * Please call ->render() everywhere manually.
	 */
	public function __toString()
	{
		if (DEVELOPMENT) {
			debug('Do not call View::__toString() as it will prevent you from obtaining a valid backtrace in case of an error.', $this->file, $this->caller ? get_class($this->caller) : null);
			debug_pre_print_backtrace();
		}
//		return $this->render().'';
		return get_class($this) . '@' . spl_object_hash($this);
	}

	public function extractImages()
	{
		$html = $this->render();
		$dom = new AdvancedHtmlDom($html);
		$scripts = $dom->find('img');
		$scripts->remove();
		$this->processed = $dom->body->innerhtml();
		return $scripts;
	}

}
