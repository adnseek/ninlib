<?php

trait DatabaseManipulation
{

	public static function getTableName()
	{
		return null;
	}

	public static function insert(DBInterface $db, array $data)
	{
		$table = static::getTableName();
		$ok = $db->runInsertQuery($table, $data);
		return $ok;
	}

	public function update(array $update)
	{
		return $this->db->runUpdateQuery(static::getTableName(), $update, [
			'id' => $this->id,
		]);
	}

}
