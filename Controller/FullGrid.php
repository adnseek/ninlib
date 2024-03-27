<?php

/**
 * Class FullGrid
 * handles sorting by columns, paging, filtering,
 * selecting visible columns
 */
abstract class FullGrid extends Grid
{

	/**
	 * @var FilterController
	 */
	public $filterController;

	/**
	 */
	public function __construct()
	{
		parent::__construct();
		// calls $this->initFilter();
	}

	public function initFilter()
	{
		// menu is making an instance of each class because of tryMenuSuffix
		//debug(get_class($this->index->controller), get_class($this), $this->request->getControllerString());
		parent::initFilter();

		$allowEdit = $this->request->getControllerString() === get_class($this);
//		debug($allowEdit);
		if ($allowEdit) {
			$this->saveFilterAndSort(get_class($this));
		}

		if (!($this->filter instanceof nadlib\Controller\Filter)) {
//			debug($this->filter);
			$this->filter = new nadlib\Controller\Filter();
//			debug(gettype2($this->filter));
		}

		$this->filterController = new FilterController();
		$this->filterController->setFilter($this->filter);
	}

	/**
	 * Will create collection object
	 * @param string $collectionName
	 * @throws LoginException
	 * @throws ReflectionException
	 */
	public function postInit($collectionName = null)
	{
		if (!$this->collection || !($this->collection instanceof Collection)) {
			$this->collection = $this->makeCollection($collectionName);
			// after construct because we need to modify join
			$this->collection->where = array_merge(
				$this->collection->where,
				$this->getFilterWhere()
			);

//			$this->log(__METHOD__, 'collection Where', $this->collection->where);

			$this->collection->postInit();
			$this->collection->pager = new Pager($this->pageSize ? $this->pageSize->get() : null);
			$this->collection->pager->setNumberOfRecords($this->collection->getCount());
			$this->collection->pager->detectCurrentPage();
		}
		// after collection is made, to run getGridColumns
		$allowEdit = $this->request->getControllerString() === get_class($this);
//		llog(get_class($this->collection));
		$this->setColumns(get_class($this), $allowEdit);
	}

	public function makeCollection($collectionName)
	{
		if (is_string($collectionName)) {
			$this->log(__METHOD__ . ' new collection', $collectionName);
			$collection = new $collectionName(null, [], $this->getOrderBy());
		} else {
			$re = new ReflectionClass($this);
			$reCol = $re->getProperty('collection');
			$doc = new DocCommentParser($reCol->getDocComment());
			$collectionName = $doc->getFirstTagValue('var');
			$collectionName = first(trimExplode('|', $collectionName));
			$this->log(__METHOD__ . ' new collection by reflection', $collectionName);
			$collection = new $collectionName();
		}
		return $collection;
	}

	/**
	 * Can't use $this->collection at this point as this function is used to initialize the collection!
	 * @return string|null
	 */
	public function getOrderBy()
	{
		$ret = '';
		$sortBy = ifsetor($this->sort['sortBy']);
		if ($this->model &&
			$this->model->thes &&
			is_array($this->model->thes[$sortBy]) &&
			ifsetor($this->model->thes[$sortBy]['source'])) {
			$sortBy = $this->model->thes[$sortBy]['source'];
		}
		if ($this->collection && $this->collection instanceof Collection && $this->collection->thes) {
			$desc = ifsetor($this->collection->thes[$sortBy]);
			//debug(array_keys($this->collection->thes), $desc);
			if (is_array($desc) &&
				ifsetor($desc['source']) &&
				ifsetor($desc['sortable']) !== false) {
				$sortBy = $desc['source'];
			}
			if (ifsetor($desc['sortable']) === false) {
				$sortBy = null;
			}
		}
		if (!$sortBy) {
//			$sortBy = new SQLOrder($this->collection->orderBy);
//			$sortBy = $sortBy->getField();
			if (!$sortBy) {
				// don't do default, because a Collection has it's own default
				//$sortBy = ifsetor($this->model->idField);
			}
		}
		if ($sortBy) {
			$ret = 'ORDER BY ' . $this->db->quoteKey($sortBy) . ' ' .
				(ifsetor($this->sort['sortOrder']) ? 'DESC' : 'ASC');
		}
		//debug($this->sort, $sortBy);
		return $ret;
	}

	public function render()
	{
		$this->setVisibleColumns();
		//$this->collection->pageSize = $this->pageSize;
		return parent::render();
	}

	public function setVisibleColumns()
	{
		if ($this->columns) {
			foreach ($this->collection->thes as $cn => $_) {
				if (!$this->columns->isVisible($cn)) {
					//unset($this->collection->thes[$cn]);
					$this->collection->thes[$cn]['!show'] = true;
				}
			}
		}
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getFilterWhere()
	{
		return $this->filterController->getFilterWhere();
	}

	public function sidebar()
	{
		$fields = $this->collection->thes;
		$content[] = $this->getFilterForm($fields);
		$content[] = $this->getColumnsForm();
		return $content;
	}

	/**
	 * @param array $fields
	 * @return HTMLForm
	 * @throws Exception
	 */
	public function getFilterForm(array $fields = [])
	{
		if (method_exists($this, 'getFilterDesc')) {
			$this->filterController->desc = $this->getFilterDesc($fields);
		} else {
			$fields = $fields ?: $this->collection->thes;
			$this->filterController->setFields($fields);
		}
		$this->filterController->linker->linkVars['c'] = get_class($this);
		return $this->filterController->render();
	}

	/**
	 * Make sure you fill the 'value' fields with data from $this->filter manually.
	 * Why manually? I don't know, it could change.
	 *
	 * @param array $fields
	 * @return array
	 * @throws Exception
	 */
	public function getFilterDesc(array $fields = null)
	{
		return $this->filterController->getFilterDesc($fields);
	}

	public function getColumnsForm()
	{
//		debug($this->getGridColumns());
//		debug($this->columns->getData());
		$desc = [
			'columns' => [
				'label' => '<h2>' . __('Visible') . '</h2>',
				'type' => 'keyset',
				'options' => $this->getGridColumns(),
				'value' => $this->columns->getData(),
				'between' => '',
			],
			'collectionName' => [
				'type' => 'hidden',
				'value' => get_class($this->collection),
			]
		];
		$f = new HTMLFormTable($desc);
		$f->method('GET');
		$f->defaultBR = true;
		$f->formHideArray($this->linker->linkVars);
		$f->showForm();
		$f->submit(__('Set Visible Columns'));
		return $f;
	}

	/**
	 * @throws Exception
	 */
	public function injectCollection()
	{
		parent::injectCollection();
		debug($this->collection->where,
			$this->getFilterWhere());
		$this->collection->where = array_merge(
			$this->collection->where,
			$this->getFilterWhere()
		);
	}

}
