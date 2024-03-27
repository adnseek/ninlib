<?php

class TestConfig extends ConfigBase
{

	public function getDB()
	{
		if (!$this->db) {
			$this->db = new DBPlacebo();
			$this->db->qb = $this->getQB();
		}
		return $this->db;
	}

	public function getQB()
	{
		return new SQLBuilder($this->getDB());
	}

}
