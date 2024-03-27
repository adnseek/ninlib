<?php

use spidgorny\nadlib\HTTP\URL;

class Pager
{

	/**
	 * Say yes, if you have PaginationControl CSS included in the header
	 * @var bool
	 */
	public static $cssOutput = false;
	public $id;
	/**
	 * Total amount of rows in database (with WHERE)
	 * Originally null to detect if setNumberOfRecords was called
	 * @var int
	 */
	public $numberOfRecords = null;
	/**
	 * Page size
	 * @var int
	 * @use setItemsPerPage
	 */
	public $itemsPerPage = 20;
	/**
	 * Offset in SQL
	 * @var int
	 */
	public $startingRecord = 0;
	/**
	 * Current Page (0+)
	 * @var int
	 */
	public $currentPage = 0;
	/**
	 * @var URL
	 */
	public $url;
	public $pagesAround = 3;
	public $showPageJump = true;
	public $showPager = true;
	/**
	 * @var PageSize
	 */
	public $pageSize;
	/**
	 * Mouse over tooltip text per page
	 * @var array
	 */
	public $pageTitles = [];
	/**
	 * @var Iterator
	 */
	public $iterator;
	/**
	 * for debugging
	 * @var string
	 */
	public $countQuery;
	/**
	 * @var int - can deviate from the valid range of pages
	 */
	public $requestedPage;
	/** @var string[] */
	public $log = [];
	/**
	 * @var Request
	 */
	protected $request;
	/**
	 * Identifies pager preferences on different pages
	 * @var string
	 */
	protected $prefix;
	/**
	 * @var UserModelInterface
	 */
	protected $user;
	/**
	 * @var DBInterface
	 */
	protected $db;

	public function __construct($itemsPerPage = null, $prefix = '')
	{
//		debug_pre_print_backtrace();
		$this->log[] = __METHOD__;
		$this->id = uniqid();
		if ($itemsPerPage instanceof PageSize) {
			$this->pageSize = $itemsPerPage;
		} else {
			$this->pageSize = new PageSize($itemsPerPage ?: $this->itemsPerPage);
		}
		$this->setItemsPerPage($this->pageSize->get()); // only allowed amounts

		$this->prefix = $prefix;
		if (class_exists('Config')) {
			$config = Config::getInstance();
			$this->db = $config->getDB();
			$this->setUser($config->getUser());
			$config->mergeConfig($this);
		}
		$this->request = Request::getInstance();
		// Inject dependencies, this breaks all projects which don't have DCI class
		//if (!$this->user) $this->user = DCI::getInstance()->user;
		$this->url = new URL();    // just in case
	}

	/**
	 * @param int $items
	 */
	public function setItemsPerPage($items)
	{
		$this->log[] = __METHOD__ . '(' . $items . ')';
		if (!$items) {
			$items = $this->pageSize->get();
		}
		$this->itemsPerPage = $items;
		if (ArrayPlus::create($this->log)->containsPartly('detectCurrentPage')) {
			$this->log[] = __METHOD__ . ' WARNING: make sure to call detectCurrentPage again';
		}
		$this->pageSize->set($items);
	}

	/**
	 * @return User|LoginUser
	 */
	public function getUser()
	{
		return $this->user;
	}

	/**
	 * @param User|LoginUser $user
	 */
	public function setUser($user)
	{
		$this->user = $user;
	}

	public function initByQuery($originalSQL)
	{
		$this->log[] = __METHOD__;
		if (is_string($originalSQL)) {
			$this->initByStringQuery($originalSQL);
		} elseif ($originalSQL instanceof SQLSelectQuery) {
			$this->initBySelectQuery($originalSQL, $originalSQL->getParameters());
		} else {
			throw new InvalidArgumentException(__METHOD__);
		}
	}

	public function initByStringQuery($originalSQL)
	{
		$this->log[] = __METHOD__;
		//debug_pre_print_backtrace();
		$key = __METHOD__ . ' (' . substr($originalSQL, 0, 300) . ')';
		TaylorProfiler::start($key);

		if (!str_contains('count(*)', $originalSQL)) {
			$queryObj = new SQLQuery($originalSQL);
			// not allowed or makes no sense
			unset($queryObj->parsed['ORDER']);
			$query = $queryObj->getQuery();
			//debug($query->parsed['WHERE']);
			$countQuery = "SELECT count(*) AS count
			FROM (" . $query . ") AS counted";
			//		debug($query.'', $query->getParameters(), $countQuery);
			//		exit();
		} else {
			$countQuery = $originalSQL;
		}
		$this->countQuery = $countQuery;
		$this->log[] = $this->countQuery;
		$res = $this->db->fetchAssoc($this->db->perform($countQuery));
		// , $query->getParameters()
		$this->setNumberOfRecords($res['count']);
		//debug($originalSQL, $query, $res);
		// validate the requested page is within the allowed range
		$this->setCurrentPage($this->requestedPage);
		TaylorProfiler::stop($key);
	}

	/**
	 * @param int $i
	 */
	public function setNumberOfRecords($i)
	{
		$this->log[] = __METHOD__ . '(' . $i . ')';
		$this->numberOfRecords = $i;
		if ($this->getStartingRecord() > $this->numberOfRecords) {    // required
			$this->setCurrentPage($this->currentPage);
		}
	}

	public function getStartingRecord()
	{
		return $this->getPageFirstItem($this->currentPage);
	}

	public function getPageFirstItem($page)
	{
		return $page * $this->itemsPerPage;
	}

	/**
	 * Make sure to setNumberOfRecords first(!)
	 * @param $page
	 */
	public function setCurrentPage($page)
	{
		$this->log[] = __METHOD__ . '(' . $page . ')';
		//max(0, ceil($this->numberOfRecords/$this->itemsPerPage)-1);    // 0-indexed
		$page = min($page, $this->getMaxPage());
		$this->currentPage = max(0, $page);
	}

	/**
	 * 0 - page 10
	 * Alternative maybe ceil($div)-1 ?
	 * @return float
	 */
	public function getMaxPage()
	{
		if ($this->itemsPerPage) {
			$div = $this->numberOfRecords / $this->itemsPerPage;
			// because a single page is 0
			$maxpage = max(0, ceil($div) - 1);
		} else {
			$maxpage = 0;
			$this->log[] = '$this->itemsPerPage: ' . $this->itemsPerPage;
		}
		$this->log[] = __METHOD__ . '->' . $maxpage;
		return $maxpage;
	}

	public function initBySelectQuery(SQLSelectQuery $originalSQL, array $parameters = [])
	{
		$this->log[] = __METHOD__;
		$key = __METHOD__ . ' (' . substr($originalSQL, 0, 300) . ')';
		TaylorProfiler::start($key);
		if (!$originalSQL->getSelect()->contains('count(*)')) {
			$queryWithoutOrder = clone $originalSQL;
			$queryWithoutOrder->unsetOrder();

			$subQuery = new SQLSubquery($queryWithoutOrder, 'counted');
			$subQuery->parameters = $parameters;

			$query = new SQLSelectQuery($this->db,
				new SQLSelect('count(*) AS count'),
				$subQuery
			);
		} else {
			$query = $originalSQL;
		}
		$this->countQuery = $query;
		$query->injectDB($this->db);

		$res = $query->fetchAssoc();
		$this->setNumberOfRecords($res['count']);
		// validate the requested page is within the allowed range
		$this->setCurrentPage($this->requestedPage);
		TaylorProfiler::stop($key);
	}

	public function getPageSize()
	{
		return $this->itemsPerPage;
	}

	/**
	 * @param $query
	 * @return string|SQLSelectQuery
	 */
	public function getSQLLimit($query)
	{
		$scheme = $this->db->getScheme();
		if ($scheme === 'ms') {
			$query = $this->db->addLimit($query, $this->itemsPerPage, $this->getStartingRecord());
		} elseif ($query instanceof SQLSelectQuery) {
			$query->setLimit(new SQLLimit($this->itemsPerPage, $this->getStartingRecord()));
		} else {
			$limit = "\nLIMIT " . $this->itemsPerPage .
				"\nOFFSET " . $this->getStartingRecord();
			$query .= $limit;
		}
		return $query;
	}

	public function getEnd()
	{
		return min($this->getStartingRecord() + $this->itemsPerPage, $this->numberOfRecords);
	}

	public function getLimit()
	{
		return $this->itemsPerPage;
	}

	public function isInPage($i)
	{
		return $i >= $this->getPageFirstItem($this->currentPage) &&
			$i < ($this->getPageFirstItem($this->currentPage) + $this->itemsPerPage);
	}

	public function renderPageSelectors(URL $url = null)
	{
		$this->log[] = __METHOD__;
		$content = '';
		if ($url) {
			$this->url = clone $url;  // this->url may be modified
		}

		$content .= '<div class="paginationControl pagination">' . "\n";
		$content .= $this->getInlineCSS();
		$content .= $this->showSearchBrowser();
		if ($this->showPager) {
			$content .= $this->renderPageSize();    // will render UL inside
		}
		$content .= '</div>';
		return $content;
	}

	public function getInlineCSS()
	{
		$content = '';
		if (!self::$cssOutput) {
			$al = AutoLoad::getInstance();
			$index = class_exists('Index') ? Index::getInstance() : null;
			if ($index && $this->request->apacheModuleRewrite()) {
				//Index::getInstance()->header['ProgressBar'] = $this->getCSS();
				$index->addCSS($al->nadlibFromDocRoot . 'CSS/PaginationControl.less');
			} elseif (false && $GLOBALS['HTMLHEADER']) {
				$GLOBALS['HTMLHEADER']['PaginationControl.less']
					= '<link rel="stylesheet" href="' . $al->nadlibFromDocRoot . 'CSS/PaginationControl.less" />';
			} elseif (!Request::isCLI()) {
				$content .= $this->getCSS();    // pre-compiles LESS inline
			}
			self::$cssOutput = true;
		}
		return $content;
	}

	public function getCSS()
	{
		if (class_exists('lessc')) {
			$l = new lessc();
			$css = $l->compileFile(dirname(__FILE__) . '/../CSS/PaginationControl.less');
			return '<style>' . $css . '</style>';
		} else {
			return '';
		}
	}

	public function showSearchBrowser()
	{
		$this->log[] = __METHOD__;
		$content = '';
		$maxpage = $this->getMaxPage();
		$pages = $this->getPagesAround($this->currentPage, $maxpage);
		//debug($pages, $maxpage);
		if ($this->currentPage > 0) {
			$link = $this->url->setParam('Pager_' . $this->prefix, ['page' => $this->currentPage - 1]);
			$link = $link->setParam('pageSize', $this->pageSize->get());
			$content .= '<li><a href="' . $link . '" rel="prev">&lt;</a></li>';
		} else {
			$content .= '<li class="disabled"><span class="disabled">&larr;</span></li>';
		}
		foreach ($pages as $k) {
			if ($k === 'gap1' || $k === 'gap2') {
				$content .= '<li class="disabled">
 					<span class="page"> &hellip; </span>
 				</li>' . "\n";
			} else {
				$content .= $this->getSinglePageLink($k, $k + 1);
			}
		}
		if ($this->currentPage < $maxpage) {
			$link = $this->url->setParam('Pager_' . $this->prefix, ['page' => $this->currentPage + 1]);
			$content .= '<li><a href="' . $link . '" rel="next">&gt;</a></li>' . "\n";
		} else {
			$content .= '<li class="disabled"><span class="disabled">&rarr;</span></li>' . "\n";
		}
		if ($this->showPageJump) {
			$form = "<form action='" . $this->url . "' method='POST' class='anyPageForm'>
				&nbsp;<input
					name='Pager_{$this->prefix}[page]'
					type='text'
					class='normal'
					value='" . ($this->currentPage + 1) . "'
					style='width: 2em; margin: 0' />
				<input type='submit' value='Page' class='submit' />
			</form>";
		} else {
			$form = '';
		}
		//debug($term);
		$content = '<ul class="pagination">' . $content . '&nbsp;' . '</ul>' . $form;
		return $content;
	}

	/**
	 * @param $current
	 * @param $max
	 * @return array
	 */
	public function getPagesAround($current, $max)
	{
		$this->log[] = __METHOD__;
		$size = $this->pagesAround;
		$pages = [];
		$k = 0;
		for ($i = 0; $i < $size; $i++) {
			$k = $i;
			if ($k >= 0 && $k < $max) {
				$pages[] = $k;
			}
		}
		if ($k + 1 < $current - $size) {
			$pages[] = 'gap1';
		}
		for ($i = -$size; $i <= $size; $i++) {
			$k = $current + $i;
			if ($k >= 0 && $k <= $max) {
				$pages[] = $k;
			}
		}
		if ($max - $size > $k + 1) {
			$pages[] = 'gap2';
		}
		for ($i = $max - $size + 1; $i <= $max; $i++) {
			$k = $i;
			if ($k >= 0 && $k <= $max) {
				$pages[] = $k;
			}
		}
		$pages = array_unique($pages);

		return $pages;
	}

	public function getSinglePageLink($k, $text)
	{
		$link = $this->url->setParam('Pager_' . $this->prefix, ['page' => $k]);
		if ($k == $this->currentPage) {
			$content = '<li class="active"><a href="' . $link . '"
				class="active"
				title="' . htmlspecialchars(ifsetor($this->pageTitles[$k], ''), ENT_QUOTES) . '"
				>' . $text . '</a></li>';
		} else {
			$content = '<li><a href="' . $link . '"
			title="' . htmlspecialchars(ifsetor($this->pageTitles[$k], ''), ENT_QUOTES) . '"
			>' . $text . '</a></li>';
		}
		return $content;
	}

	public function renderPageSize()
	{
		$this->log[] = __METHOD__;
		$this->pageSize->setURL(new URL(null, []));
		$this->pageSize->set($this->itemsPerPage);
		$content = '<div class="pageSize pull-right floatRight">' .
			$this->pageSize->render() . '&nbsp;' . __('per page') . '</div>';
		return $content;
	}

	public function debug()
	{
		return [
			'pager hash' => spl_object_hash($this),
			'numberOfRecords' => $this->numberOfRecords,
			'itemsPerPage' => $this->itemsPerPage,
			'pager->log' => $this->log,
			'pageSize->get' => $this->pageSize->get(),
			'pageSize->log' => $this->pageSize->log,
			'currentPage [0..]' => $this->currentPage,
			'floatPages' => $this->numberOfRecords / $this->itemsPerPage,
			'getMaxPage()' => $this->getMaxPage(),
			'startingRecord' => $this->getStartingRecord(),
			//'getSQLLimit()' => $this->getSQLLimit(),
			'getPageFirstItem()' => $this->getPageFirstItem($this->currentPage),
			'getPageLastItem()' => $this->getPageLastItem($this->currentPage),
			'getPagesAround()' => $pages = $this->getPagesAround($this->currentPage, $this->getMaxPage()),
			'url' => $this->url . '',
			'pagesAround' => $this->pagesAround,
			'showPageJump' => $this->showPageJump,
			'showPager' => $this->showPager,
			'prefix' => $this->prefix,
		];
	}

	public function getPageLastItem($page)
	{
		return min($this->numberOfRecords, $page * $this->itemsPerPage + $this->itemsPerPage);
	}

	public function __toString()
	{
		$properties = get_object_vars($this);
		unset($properties['graphics']);
		foreach ($properties as $key => &$val) {
			if (is_object($val) && method_exists($val, '__toString')) {
				$val = $val->__toString();
			} elseif (is_array($val)) {
				foreach ($val as &$v) {
					if (is_array($v)) {
						$v = $v->__toString();
					}
				}
			}
		}
		return '<blockquote style="background-color: silver; border: solid 1px lightblue;"><pre>' . get_class($this) . ' [' . print_r($properties, true) . ']</pre></blockquote>';
	}

	public function getURL()
	{
		return $this->url . '&pager[page]=' . ($this->currentPage);
	}

	public function getObjectInfo()
	{
		return get_class($this) . ': "' . $this->itemsPerPage . '" (#' . spl_object_hash($this) . ')';
	}

	public function loadMoreButton($controller)
	{
		$content = '';
		//debug($pager->currentPage, $pager->getMaxPage());
		if ($this->currentPage < $this->getMaxPage()) {
			$loadPage = $this->currentPage + 1;
			$f = new HTMLForm();
			$f->hidden('c', $controller);
			$f->hidden('action', 'loadMore');
			$f->hidden('Pager.[page]', $loadPage);
			$f->formHideArray([$this->prefix => $this->request->getArray($this->prefix)]);
			$f->formMore = 'onsubmit="return ajaxSubmitForm(this);"';
			$f->submit(__('Load more'), ['class' => 'btn']);
			$content .= '<div id="loadMorePage' . $loadPage . '">' . $f . '</div>';
		}
		return $content;
	}

	public function setIterator(Iterator $iterator)
	{
		$this->log[] = __METHOD__;
		$this->iterator = $iterator;
		$this->setNumberOfRecords($iterator->count());
		$this->detectCurrentPage();    // why?
	}

	/**
	 * To be called only after setNumberOfRecords()
	 */
	public function detectCurrentPage()
	{
		if (null === $this->numberOfRecords) {
			throw new InvalidArgumentException('Pager->detectCurrentPage() called before Pager->setNumberOfRecords()');
		}
		$pagerData = ifsetor(
			$_REQUEST['Pager.' . $this->prefix],
			ifsetor($_REQUEST['Pager_' . $this->prefix])
		);
//		debug($pagerData);
		$this->log[] = __METHOD__ . ': ' . json_encode($pagerData);
		if ($pagerData) {
			// when typing page number in [input] box
			if ($this->request->isPOST() && ifsetor($pagerData['decrement'])) {
				//Debug::debug_args($pagerData);
				$pagerData['page']--;
			}
			$this->setCurrentPage($pagerData['page']);
			$this->saveCurrentPage();
		} elseif ($this->user && method_exists($this->user, 'getPref')) {
			$pager = $this->user->getPref('Pager.' . $this->prefix);
			if ($pager) {
				//debug(__METHOD__, $this->prefix, $pager['page']);
				$this->setRequestedPage($pager['page']);
			}
		} else {
			$this->setRequestedPage(0);
		}
	}

	public function saveCurrentPage()
	{
		$this->log[] = __METHOD__;
		//debug(__METHOD__, $this->prefix, $this->currentPage);
		if ($this->user instanceof UserWithPreferences) {
			$this->user->setPref('Pager.' . $this->prefix, [
				'page' => $this->currentPage
			]);
		}
	}

	public function setRequestedPage($page)
	{
		$this->requestedPage = $page;
	}

	public function getPageData()
	{
		$data = [];

		$start = $this->getStart();
		for ($i = 0; $i < $start; $i++) {
			$this->iterator->next();
		}

		$size = $this->itemsPerPage;
		for ($i = 0; $i < $size; $i++) {
			if ($this->iterator->valid()) {
				$data[] = $this->iterator->current();
				$this->iterator->next();
			}
		}

		return $data;
	}

	public function getStart()
	{
		return $this->getStartingRecord();
	}

	public function slice(array $data)
	{
		$this->log[] = __METHOD__;
		$this->setNumberOfRecords(sizeof($data));
		$this->detectCurrentPage();
		return array_slice($data,
			$this->getStart(), $this->pageSize->get(), true);
	}

	public function bulma()
	{
		$prevPageLink = URL::getCurrent()->addParams(['page' => $this->currentPage - 1]);
		$nextPageLink = URL::getCurrent()->addParams(['page' => $this->currentPage + 1]);
		$prevDisabled = $this->currentPage === 0 ? 'disabled' : '';
		$nextDisabled = $this->currentPage === $this->getMaxPage() ? 'disabled' : '';
		$content[] = '<nav class="pagination" role="navigation" aria-label="pagination">
  <a href="' . $prevPageLink . '" class="pagination-previous" ' . $prevDisabled . '>Previous</a>
  <a href="' . $nextPageLink . '" class="pagination-next" ' . $nextDisabled . '>Next page</a>
  <ul class="pagination-list">';
		foreach ($this->getVisiblePages() as $page) {
			if (str_startsWith($page, 'gap')) {
				$content[] = '<li>
      <span class="pagination-ellipsis">&hellip;</span>
    </li>';
			} else {
				$pageLink = URL::getCurrent()->addParams(['page' => $page]);
				$isCurrent = $this->currentPage === $page;
				$isCurrentClass = $isCurrent ? 'is-current' : '';
				$isCurrentAria = $isCurrent ? 'aria-current="page"' : '';
				$content[] = '
    <li>
      <a href="' . $pageLink . '" class="pagination-link ' . $isCurrentClass . '" aria-label="Page 1" ' . $isCurrentAria . '>' . ($page + 1) . '</a>
    </li>';
			}
		}
		$content[] = '
  </ul>
</nav>';
		return implode(PHP_EOL, $content);
	}

	public function getVisiblePages()
	{
		return $this->getPagesAround($this->currentPage, $this->getMaxPage());
	}

}
