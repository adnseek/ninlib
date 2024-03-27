<?php

interface AlterTableInterface
{

	/**
	 * CREATE TABLE $table ...
	 * @param string $table
	 * @param array $columns
	 * @return mixed
	 */
	public function getCreateQuery($table, array $columns);

	/**
	 * ALTER TABLE CHANGE COLUMN $oldName ...
	 * @param string $table
	 * @param string $oldName
	 * @param TableField $index
	 * @return mixed
	 */
	public function getAlterQuery($table, $oldName, TableField $index);

	/**
	 * ALTER TABLE ADD COLUMN ...
	 * @param string $table
	 * @param TableField $index
	 * @return mixed
	 */
	public function getAddQuery($table, TableField $index);

	public function getFieldParams(TableField $index);

}
