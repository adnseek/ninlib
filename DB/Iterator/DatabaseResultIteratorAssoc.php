<?php

/**
 * This class is a replacement for fetchAll - foreach combination and should be used to reduce the memory
 * requirements of the script. It's meant to mimic Iterator classes in PHP5, but doesn't inherit the interface
 * completely. (wrong!?)
 */
class DatabaseResultIteratorAssoc extends DatabaseResultIterator
{

	public function retrieveRow()
	{
		$this->log(__METHOD__);
		$row = $this->db->fetchAssoc($this->dbResultResource);
		return $row;
	}

}
