<?php

/**
 * Class IteratorMemberCollection
 * @deprecated
 * Fatal error: Class IteratorCollection cannot implement both Iterator and IteratorAggregate at the same time
 */
class CollectionMock
{

	public $pid;
	public $where;
	public $order;
	public $members;

	public function __construct($pid = null, $where = [], $order = '')
	{
		$this->pid = $pid;
		$this->where = $where;
		$this->order = $order;
	}

	public function objectify()
	{
	}

}
