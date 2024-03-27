<?php

class CollectionQueryMySQL extends CollectionQuery
{

	public $count;
	public $parentID;
	public $parentField;

	/**
	 * https://dev.mysql.com/doc/refman/5.0/en/information-functions.html#function_found-rows
	 * @requires PHP 5.3
	 * @return array
	 * @throws DatabaseException
	 * @throws MustBeStringException
	 */
	public function retrieveDataFromMySQL()
	{
		$taylorKey = get_class($this) . '::' . __FUNCTION__ .
			" (" . $this->parentField . ':' . (is_array($this->parentID)
				? json_encode($this->parentID)
				: $this->parentID) . ")";
		TaylorProfiler::start($taylorKey);
		/** @var SQLSelectQuery $query */
		$query = $this->getQuery();
		if (class_exists('PHPSQL\Parser') && false) {
			$sql = new SQLQuery($query);
			$sql->appendCalcRows();
			$this->query = $sql->__toString();
		} else {
			//$this->query = str_replace('SELECT ', 'SELECT SQL_CALC_FOUND_ROWS ', $query);	// subquery problem
			$this->query = preg_replace('/SELECT /', 'SELECT SQL_CALC_FOUND_ROWS ', $query, 1);
		}

		if ($query instanceof SQLSelectQuery) {
			$params = $query->getParameters();
			$res = $this->db->perform($this->query, $params);
		} else {
			$res = $this->db->perform($this->query);
		}

		if ($this->pager) {
			$this->pager->setNumberOfRecords(PHP_INT_MAX);
			$this->pager->detectCurrentPage();
			//$this->pager->debug();
		}
		$start = $this->pager ? $this->pager->getStart() : 0;
		$limit = $this->pager ? $this->pager->getLimit() : PHP_INT_MAX;

		//debug($sql.'', $start, $limit);
		$data = $this->db->fetchPartition($res, $start, $limit);

		$resFoundRows = $this->db->perform('SELECT FOUND_ROWS() AS count');
		$countRow = $this->db->fetchAssoc($resFoundRows);
		$this->count = $countRow['count'];

		if ($this->pager) {
			$this->pager->setNumberOfRecords($this->count);
			$this->pager->detectCurrentPage();
			//$this->pager->debug();
		}
		TaylorProfiler::stop($taylorKey);
		return $data;
	}

}
