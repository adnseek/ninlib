<?php

class PageProfiler
{

	/**
	 * @var Request
	 */
	public $request;

	/**
	 * @var HTML
	 */
	public $html;

	/**
	 * @phpstan-consistent-constructor
	 */
	public function __construct()
	{
		$this->request = Request::getInstance();
		$this->html = new HTML();
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function render()
	{
		$content = [];
		if ($this->canOutput()) {
			$content[] = '<div class="profiler noprint">';
			$content[] = $this->getURL();
			$content[] = $this->getGET();
			$content[] = $this->getPOST();
			$content[] = $this->getHeader();
			$content[] = $this->getFooter();
			$content[] = $this->getSession();
			$content[] = $this->html->s(OODBase::getCacheStatsTable());
			$content[] = $this->getTaylorProfiler();
			$content[] = '</div>';

			$ft = new FloatTime(true);
			$content[] = $ft->render();
		}
		return $content;
	}

	public function canOutput()
	{
		if (class_exists('Index')) {
			$index = Index::getInstance();
			$exceptions = in_array($index->controller ? get_class($index->controller) : null, ['Lesser']);
		} else {
			$exceptions = false;
		}
		$debug_page = isset($_COOKIE['debug_page'])
			? $_COOKIE['debug_page']
			: ifsetor($_COOKIE['debug']);

		return DEVELOPMENT
			&& !$this->request->isAjax()
			&& !$exceptions
			&& !$this->request->isCLI()
			&& $debug_page;
	}

	/**
	 * @return string
	 */
	protected function getURL()
	{
		$url = clone $this->request->getURL();
		$url->makeRelative();
		$params = $url->getParams();
		$url->clearParams();
		$fullURL = $this->request->getLocation() . $url;
		$urlText = $this->request->getLocation() . ' ' . $url;
		$content = '<a href="' . $fullURL . '">' . $urlText . '</a>' . BR;
		return $content;
	}

	/**
	 * @return string
	 */
	protected function getGET()
	{
		$content = '';
		$url = $this->request->getURL();
		$params = $url->getParams();
		$content .= $this->html->h4('GET');
		$content .= $this->html->pre(json_encode($params, JSON_PRETTY_PRINT));
		return $content;
	}

	/**
	 * @return string
	 */
	protected function getPOST()
	{
		$content = '';
		$content .= $this->html->h4('POST');
		$content .= $this->html->pre(json_encode($_POST, JSON_PRETTY_PRINT));
		return $content;
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	protected function getHeader()
	{
		if (!class_exists('Index')) {
			return '';
		}
		$content = '';
		$index = null;
		if (class_exists('Index')) {
			$index = Index::getInstance();
		}
		$content .= $this->html->h4('Header');

		$header = '';
		if ($index) {
			$header = json_encode($index->header, JSON_PRETTY_PRINT);
		}
		$header = str_replace('\/', '/', $header);
		$header = str_replace('\"', '"', $header);
		$content .= $this->html->pre($header);
		return $content;
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	protected function getFooter()
	{
		$content = '';
		$index = Index::getInstance();
		$content .= $this->html->h4('Footer');
		$footer = json_encode($index->footer, JSON_PRETTY_PRINT);
		$footer = str_replace('\/', '/', $footer);
		$footer = str_replace('\"', '"', $footer);
		$content .= $this->html->pre($footer);
		return $content;
	}

	/**
	 * @return string
	 */
	protected function getSession()
	{
		$content = '';
		$content .= $this->html->h4('Session');
		$session = json_encode($_SESSION, JSON_PRETTY_PRINT);
		$session = str_replace('\/', '/', $session);
		$content .= $this->html->pre($session);
		return $content;
	}

	protected function getTaylorProfiler()
	{
		$content = [];
		/** @var $profiler TaylorProfiler */
		$profiler = TaylorProfiler::getInstance();
		if ($profiler) {
			$content[] = $profiler->printTimers(true);
			$content[] = TaylorProfiler::dumpQueries();
			//$content[] = $profiler->printTrace(true);
			//$content[] = $profiler->analyzeTraceForLeak();
		}
		return $content;
	}

}
