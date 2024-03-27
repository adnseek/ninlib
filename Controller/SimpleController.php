<?php

use nadlib\IndexInterface;
use spidgorny\nadlib\HTTP\URL;

/**
 * Class SimpleController
 * @mixin HTML
 * @method error($content, $httpCode = 500)
 * @method info($content)
 * @method success($content)
 */
abstract class SimpleController
{

	/**
	 * Instance per class
	 * @var Controller[]
	 */
	protected static $instance = [];

	/**
	 * @var Index|IndexInterface
	 */
	public $index;

	/**
	 * @var Request
	 * @public for injecting something in PHPUnit
	 */
	public $request;

	/**
	 * Will be taken as a <title> of the HTML table
	 * @var string
	 */
	public $title;

	public $encloseTag = 'h2';

	public $log = [];

	/**
	 * @var HTML
	 */
	protected $html;

	public function __construct()
	{
		if (ifsetor($_REQUEST['d']) === 'log') {
			echo get_class($this) . '::' . __METHOD__ . BR;
		}
		$this->index = class_exists('Index', false)
			? Index::getInstance() : null;
		$this->request = Request::getInstance();
		$this->title = $this->title ?: last(trimExplode('\\', get_class($this)));
		//debug_pre_print_backtrace();
		$this->html = new HTML();
		self::$instance[get_class($this)] = $this;
	}

	/**
	 * @return static
	 * @throws Exception
	 */
	public static function getInstance()
	{
		$static = get_called_class();
		//if ($static == 'Controller') throw new Exception('Unable to create Controller instance');
		$isset = isset(self::$instance[$static]);
		//debug(array_keys(self::$instance), $static, $isset);
		if ($isset) {
			$result = self::$instance[$static];
		} else {
			$index = Index::getInstance();
			if ($index->controller instanceof $static) {
				$result = $index->getController();
			} else {
				// @phpstan-ignore-next-line
				$result = new $static();
			}
		}
		//debug($isset, get_class($index), get_class($result));
		return $result;
	}

	public function __call($method, array $arguments)
	{
		if (method_exists($this->html, $method)) {
			return call_user_func_array($this->html->$method, $arguments);
		}

		throw new RuntimeException('Method ' . $method . ' not found in ' . get_class($this));
	}

	/**
	 * Combines params with $this->linkVars
	 * Use makeURL() for old functionality
	 * @param array $params
	 * @param null $prefix
	 * @return URL
	 */
	public function getURL(array $params = [], $prefix = null)
	{
		if ($params || $prefix) {
			throw new InvalidArgumentException('Use makeURL() instead of ' . __METHOD__);
		}
		//		$params = $params + $this->linkVars;
		//		debug($params);
		//		return $this->makeURL($params, $prefix);
		return ClosureCache::getInstance(spl_object_hash($this), static function () {
			return new URL();
		})->get();
	}

	/**
	 * This function prevents performAction() from doing nothing
	 * if there is a __CLASS__.phtml file in the same folder
	 * @return MarkdownView|string|View|string[]
	 */
	public function indexAction()
	{
		$content = $this->renderTemplate();
		return $this->html->div($content, str_replace('\\', '-', get_class($this)));
	}

	public function renderTemplate()
	{
		$filePHTML = get_class($this) . '.phtml';
		$fileMD = get_class($this) . '.md';

		$reflector = new ReflectionClass(get_class($this));
		$classDir = dirname($reflector->getFileName());
		if (file_exists('template/' . $filePHTML)) {
			$content = new View($filePHTML, $this);
		} elseif (file_exists('template/' . $fileMD)) {
			$content = new MarkdownView($fileMD, $this);
		} elseif (file_exists($classDir . '/' . $filePHTML)) {
			$content = new View($classDir . '/' . $filePHTML, $this);
		} elseif (file_exists($classDir . '/' . $fileMD)) {
			$content = new MarkdownView($classDir . '/' . $fileMD, $this);
		} else {
			$content = '';
		}

		//		debug($filePHTML, $fileMD);

		return is_object($content)
			? $content->render()
			: $content;
	}

	public function render()
	{
		$content[] = $this->performAction();
		return $content;
	}

	/**
	 * Will call indexAction() method if no $action provided
	 * @param $action
	 * @return false|mixed|string
	 * @throws ReflectionException
	 */
	public function performAction($action = null)
	{
		$content = '';
		$method = $action ?? $this->detectAction();
		if ($method) {
			$method .= 'Action';        // ZendFramework style
			//			debug($method, method_exists($this, $method));

			$proxy = $this->request->getTrim('proxy');
			if ($proxy) {
				$proxy = new $proxy($this);
			} else {
				$proxy = $this;
			}

			// other classes except main controller may result in multiple messages
			if (method_exists($proxy, $method)) {
				if (Request::isCLI()) {
					$assoc = array_slice(ifsetor($_SERVER['argv'], []), 3);
					$content = call_user_func_array([$proxy, $method], $assoc);
				} else {
					$caller = new MarshalParams($proxy);
					$content = $caller->call($method);
				}
			}
		}
		return $content;
	}

	public function detectAction()
	{
		if (Request::isCLI()) {
			//debug($_SERVER['argv']);
			$reqAction = ifsetor($_SERVER['argv'][2]);    // it was 1
		} else {
			$reqAction = $this->request->getTrim('action');
		}
		//		debug($reqAction);
		return $reqAction;
	}

	/**
	 * Wraps the content in a div/section with a header.
	 * The header is linkable.
	 * @param string|array|ToStringable $content
	 * @param string $caption
	 * @param string $h
	 * @param array $more
	 * @return ToStringable
	 * @throws Exception
	 */
	public function encloseInAA($content, $caption = '', $h = null, array $more = [])
	{
		$h = $h ? $h : $this->encloseTag;
		$content = $this->s($content);
		if ($caption) {
			$content = [
				'caption' => $this->getCaption($caption, $h),
				$content
			];
		}
		$more['class'] = ifsetor($more['class'], 'padding clearfix');
		$more['class'] .= ' ' . get_class($this);
		//debug_pre_print_backtrace();
		//$more['style'] = "position: relative;";	// project specific
		$content = new HTMLTag('section', $more, $content, true);
		return $content;
	}

	public function s($something)
	{
		return MergedContent::mergeStringArrayRecursive($something);
	}

	public function getCaption($caption, $hTag)
	{
		return '<' . $hTag . '>' .
			$caption .
			'</' . $hTag . '>';
	}

	public function __toString()
	{
		return $this->s($this->render());
	}

	public function log($action, ...$data)
	{
		llog($action, ...$data);
		if (count($data) === 1) {
			$data = $data[0];
		}
		$this->log[] = new LogEntry($action, $data);
	}

}
