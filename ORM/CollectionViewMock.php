<?php

use spidgorny\nadlib\HTTP\URL;

class CollectionViewMock
{

	/**
	 * @var Collection
	 */
	public $collection;

	public function __construct(Collection $col)
	{
		$this->collection = $col;
	}

	public function renderTable()
	{
		TaylorProfiler::start(__METHOD__ . " ({$this->collection->table})");
		$this->collection->log(get_class($this) . '::' . __FUNCTION__ . '()');
		if ($this->collection->getCount()) {
			$this->prepareRender();
			//debug($this->tableMore);
			$s = $this->collection->getView()->getDataTable();
			if ($this->collection->pager) {
				$url = new URL();
				$pages = $this->collection->pager->renderPageSelectors($url);
				$content = $pages . $s->getContent(get_class($this)) . $pages;
			} else {
				$content = $s;
			}
		} else {
			$content = '<div class="message alert alert-warning">' .
				__($this->collection->getView()->noDataMessage) . '</div>';
		}
		TaylorProfiler::stop(__METHOD__ . " ({$this->collection->table})");
		return $content;
	}

	public function prepareRender()
	{
		TaylorProfiler::start(__METHOD__ . " ({$this->collection->table})");
		$this->collection->log(get_class($this) . '::' . __FUNCTION__ . '()');
		$data = $this->collection->getData();
		foreach ($data as $i => $row) { // Iterator by reference (PHP 5.4.15 crash)
			$row = $this->collection->prepareRenderRow($row);
			$data[$i] = $row;
		}
		$this->collection->setData($data);
		TaylorProfiler::stop(__METHOD__ . " ({$this->collection->table})");
	}

}
