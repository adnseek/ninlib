<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Majid (Pedram) Jokar
 * Date: 27.09.13
 * Time: 18:02
 * To change this template use File | Settings | File Templates.
 */

class CollectionMM extends Collection
{

	/**
	 * cross-reference table name
	 *
	 * @var string
	 */
	public $table = '';

	/**
	 * @var string
	 */
	public $idField = '';

	/**
	 * @var string
	 */
	public $field1 = '';

	/**
	 * @var string
	 */
	public $field2 = '';

	public $orderBy = '';

	/**
	 * @param string $field1
	 * @param string $field2
	 */
	public function __construct($field1, $field2)
	{
		$this->field1 = $field1;
		$this->field2 = $field2;

		parent::__construct();
	}

	/**
	 * @param $id
	 */
	public function getField1Values($id)
	{
		$this->idField = $this->field1;
		$this->where[$this->field2] = $id;
		$this->retrieveData();
	}

	/**
	 * @param $id
	 */
	public function getField2Values($id)
	{
		$this->idField = $this->field2;
		$this->where[$this->field1] = $id;
		$this->retrieveData();
	}

	/**
	 * Will detect double-call and do nothing.
	 *
	 * @param string $class - required, but is supplied by the subclasses
	 * @param bool $byInstance
	 * @return object[]
	 */
	public function objectify($class = '', $byInstance = false)
	{
		$this->members = [];   // somehow necessary
		$class = $class ? $class : $this->itemClassName;
		if (!$this->members) {
			foreach ($this->getData() as $row) {
				$key = $row[$this->idField];
				if ($byInstance) {
					$this->members[$key] = call_user_func($class . '::getInstance', $key);
				} else {
					$this->members[$key] = new $class($key);
				}
			}
		}
		return $this->members;
	}

}
