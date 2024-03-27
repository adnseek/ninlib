<?php

class DatabaseInstanceIterator extends DatabaseResultIteratorAssoc
{

	public $className;

	public function __construct(DBInterface $db, $className)
	{
		parent::__construct($db);
		$this->className = $className;
	}

	public function retrieveRow()
	{
		$row = parent::retrieveRow();  // assoc
		if ($row) {
			//debug($row, $this->className);
			if (method_exists($this->className, 'getInstance')) {
				$obj = call_user_func([$this->className, 'getInstance'], $row);
			} else {
				$obj = new $this->className($row);
			}
			return $obj;
		}
		return false;    // @see isValid()
	}

}
