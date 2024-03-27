<?php

abstract class IteratorMemberCollection extends CollectionMock implements Iterator, Countable
{

	public function __construct($pid = null, /*array/SQLWhere*/ $where = [], $order = '')
	{
		parent::__construct($pid, $where, $order);
		//$this->objectify();   // may cause data retrieval without $where
	}

	public function rewind(): void
	{
		$this->objectify();
		reset($this->members);
	}

	public function current(): mixed
	{
		return current($this->members);
	}

	public function key(): mixed
	{
		return key($this->members);
	}

	public function next(): void
	{
		next($this->members);
	}

	public function valid(): bool
	{
		return $this->key() !== null;
	}

	public function count(): int
	{
		$this->objectify();
		return sizeof($this->members);
	}

}
