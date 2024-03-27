<?php

class SQLWhereNotEqual extends SQLWhereEqual
{

	public function __toString()
	{
		if (is_numeric($this->val)) {    // leading 0 leads to problems
			$sql = "({$this->field} != " . $this->val . "
			AND {$this->field} != '" . $this->val . "')";
		} elseif (is_null($this->val)) {
			$sql = $this->field . ' IS NOT NULL';
		} else {
			$sql = $this->field . ' != ' . $this->db->quoteSQL($this->val);
		}
		return $sql;
	}

}
