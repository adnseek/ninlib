<?php

use spidgorny\nadlib\HTTP\URL;

class Linker
{

	/**
	 * Will be set according to mod_rewrite
	 * Override in __construct()
	 * @public to be accessed from Menu
	 * @var bool
	 */
	public $useRouter = false;

	public $linkVars = [];

	public $controllerName;

	/**
	 * @var Request
	 */
	public $request;

	public function __construct($controllerName, Request $request)
	{
		$this->controllerName = $controllerName;
		$this->request = $request;
	}

	/**
	 * Returns '<a href="$page?$params" $more">$text</a>
	 * @param string $text
	 * @param array $params
	 * @param string $page
	 * @param array $more
	 * @param bool $isHTML
	 * @return HTMLTag
	 */
	public function makeLink($text, array $params, $page = '', array $more = [], $isHTML = false)
	{
		//debug($text, $params, $page, $more, $isHTML);
		$content = new HTMLTag('a', [
				'href' => $this->makeURL($params, $page),
			] + $more, $text, $isHTML);
		return $content;
	}

	/**
	 * @param array|string $params
	 * @param null $prefix
	 * @return URL
	 * @public for View::link
	 * @use getURL()
	 */
	public function makeURL(array $params, $prefix = null)
	{
		if (!$prefix && $this->useRouter) { // default value is = mod_rewrite
			$class = ifsetor($params['c']);
			if ($class && !$prefix) {
				unset($params['c']);    // RealURL
				$prefix = $class;
			} else {
				$class = null;
			}
		} else {
			$class = null;
			// this is the only way to supply controller
			//unset($params['c']);
		}

		$location = '';
		if (!str_startsWith($prefix, 'http')) {
			$location = $this->request->getLocation();
		}
		$url = new URL($prefix
			? $location . $prefix
			: $location, $params);
		$path = $url->getPath();
		if ($this->useRouter && $class) {
			$path->setFile($class);
			$path->setAsFile();
		}
		//debug($prefix, get_class($path));
		$url->setPath($path);
		nodebug([
			'method' => __METHOD__,
			'params' => $params,
			'prefix' => $prefix,
			'useRouter' => $this->useRouter,
			'class' => $class,
			'class($url)' => get_class($url),
			'class($path)' => get_class($path),
			'$this->linkVars' => $this->linkVars,
			'return' => $url . '',
			'location' => $location . '',
		]);
		return $url;
	}

	public function makeAjaxLink($text, array $params, $div, $jsPlus = '', $aMore = [], $prefix = '')
	{
		$url = $this->makeURL($params, $prefix);
		$link = new HTMLTag('a', $aMore + [
				'href' => $url,
				'onclick' => '
			jQuery(\'#' . $div . '\').load(\'' . $url . '\');
			return false;
			' . $jsPlus,
			], $text, true);
		return $link;
	}

	/**
	 * @param array $params
	 * @return URL
	 * @throws Exception
	 * @see makeRelURL
	 */
	public function adjustURL(array $params)
	{
		return URL::getCurrent()->addParams([
				'c' => $this->controllerName,
			] + $params);
	}

	/**
	 * Just appends $this->linkVars
	 * @param string $text
	 * @param array $params
	 * @param string $page
	 * @return HTMLTag
	 */
	public function makeRelLink($text, array $params, $page = '?')
	{
		return new HTMLTag('a', [
			'href' => $this->makeRelURL($params, $page)
		], $text);
	}

	/**
	 * Only appends $this->linkVars to the URL.
	 * Use this one if your linkVars is defined.
	 * @param array $params
	 * @param string $page
	 * @return URL
	 */
	public function makeRelURL(array $params = [], $page = null)
	{
		return $this->makeURL(
			$params                           // 1st priority
			+ (new URL())->getParams()            // 2nd priority
			+ $this->linkVars,
			$page
		);                // 3rd priority
	}

	/**
	 * There is no $formMore parameter because you get the whole form returned.
	 * You can modify it after returning as you like.
	 * @param string|HtmlString $name - if object then will be used as is
	 * @param string|null $action
	 * @param string $formAction
	 * @param array $hidden
	 * @param string $submitClass
	 * @param array $submitParams
	 * @return HTMLForm
	 */
	public function getActionButton($name, $action, $formAction = null, array $hidden = [], $submitClass = '', array $submitParams = [])
	{
		$f = new HTMLForm();
		if ($formAction) {
			$f->action($formAction);
		} else {
			$bt = debug_backtrace();
//			debug($bt[2]);
			// this autodetection is tricky
			// better provide $formAction = '?c=SomeController'
			$f->hidden('c', get_class($bt[2]['object']));
		}
		$f->formHideArray($hidden);
		if (false) {    // this is too specific, not and API
//			if ($id = $this->request->getInt('id')) {
//				$f->hidden('id', $id);
//			}
		}
		if (!is_null($action)) {
			$f->hidden('action', $action);
		}
		if ($name instanceof HtmlString) {
			$f->button($name, [
					'type' => "submit",
					'id' => 'button-action-' . $action,
					'class' => $submitClass,
				] + $submitParams);
		} else {
			$f->submit($name, [
					'id' => 'button-action-' . $action,
					'class' => $submitClass,
				] + $submitParams);
		}
		return $f;
	}

	public function linkToAction($action = '', array $params = [], $controller = null)
	{
		if (!$controller) {
			$controller = $this->controllerName;
		}
		$params = [
				'c' => $controller,
			] + $params;
		if ($action) {
			$params += [
				'action' => $action,
			];
		}
		return $this->makeURL($params);
	}

	public function linkPage($className, array $params = [])
	{
		/** @var AppController $obj */
		$obj = new $className();
		$title = $obj->title;
		$html = new HTML();
		$href = $className::href($params);
		return $html->a($href, $title);
	}

	public function makeActionURL($action = '', array $params = [], $path = '')
	{
		$urlParams = [
				'c' => $this->controllerName,
				'action' => $action,
			] + $params;
		$urlParams = array_filter($urlParams);
		return $this->makeURL($urlParams, $path);
	}

}
