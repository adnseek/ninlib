<?php

/**
 * Class ModelWithCollection
 * Collection code is deprecated, but we keep it here for some time, just in case.
 */
class ModelWithCollection extends Model
{

	public $itemClassName = '?';

	/**
	 * @param array $where
	 * @param null $orderBy
	 * @return Collection
	 * @deprecated
	 */
	public function getCollection(array $where = [], $orderBy = null)
	{
		$col = Collection::createForTable($this->db, $this->table);
		$col->idField = $this->idField;
		$col->itemClassName = $this->itemClassName;
		$col->objectifyByInstance = method_exists($this->itemClassName, 'getInstance');
		$col->where = $where;
		if ($orderBy) {
			$col->orderBy = $orderBy;
		}

		// because it will try to run query on DBLayerJSON
		// that's OK because we don't use DBLayerJSON anymore
//		$col->count = $this->getCount();
		return $col;
	}

	/**
	 * @param $id
	 * @return mixed
	 * @deprecated
	 */
	public function getModel($id)
	{
		$model = call_user_func([$this->itemClassName, 'getInstance'], $id);
		return $model;
	}

	/**
	 * TODO: implement numRows in a way to get the amount of data from the query
	 * object.
	 * @return int
	 */
	public function getCount()
	{
		// don't uncomment as this leads to recursive calls to $this->getCollection()
		return $this->getCollection()->getCount();
//		return $this->db->numRows('SELECT count(*) FROM '.$this->table);
	}

}
