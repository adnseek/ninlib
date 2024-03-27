<?php

class SQLWhereEqualText extends SQLWhereEqual
{

	public function __toString()
	{
		if (is_null($this->val)) {
			$sql = $this->field . ' IS NULL';
		} else {
			$sql = $this->field . ' = ' . $this->db->quoteSQL($this->val);
		}
		return $sql;
	}

}
