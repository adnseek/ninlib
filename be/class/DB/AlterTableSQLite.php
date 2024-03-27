<?php

class AlterTableSQLite extends AlterTableHandler implements AlterTableInterface
{

	public function getCreateQuery($table, array $columns)
	{
		$set = [];
		foreach ($columns as $row) {
			$col = TableField::init($row);
			$set[] = $col->field . ' ' . $col->type . ' '
				. ($col->isNull ? 'NULL' : 'NOT NULL');
		}
		//debug($col);
		return 'CREATE TABLE ' . $table . ' (' . implode(",\n", $set) . ');';
	}

	public function getFieldParams(TableField $index)
	{
		$default = $index->default
			? (in_array($index->default, $this->db->getReserved())
				? $index->default
				: $this->db->quoteSQL($index->default))
			: '';
		return ' ' . trim(
				(($index->isNull == 'NO') ? 'NOT NULL' : 'NULL') .
				' ' . ($index->collation ? 'COLLATE ' . $index->collation : '') .
				' ' . ($index->default ? "DEFAULT " . $default : '') .    // must not be quoted for CURRENT_TIMESTAMP
				' ' . ($index->comment ? "COMMENT '" . $index->comment . "'" : '') .
				' ' . (($index->key == 'PRI') ? "PRIMARY KEY" : '') .
				' ' . implode(' ', $index->extra));
	}

	public function getAlterQuery($table, $oldName, TableField $index)
	{
		$query = 'ALTER TABLE ' . $table . ' ADD COLUMN ' . $oldName . ' ' . $index->field .
			' ' . $index->type .
			$this->getFieldParams($index);
		return $query;
	}

	public function getAddQuery($table, TableField $index)
	{
		$query = 'ALTER TABLE ' . $table . ' ADD COLUMN ' . $index->field .
			' ' . $index->type .
			$this->getFieldParams($index);
		return $query;
	}

}
