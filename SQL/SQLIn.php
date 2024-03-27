<?php

class SQLIn extends SQLWherePart
{

	public $list = [];

	public function __construct(array $list, $field = null)
	{
		parent::__construct();
		$this->list = $list;
		foreach ($this->list as $el) {
			if (is_array($el)) {
				throw new InvalidArgumentException(__METHOD__ . ' need to have flat array');
			}
		}
		$this->injectField($field);
	}

	public function __toString()
	{
		$field = $this->field;

		// this prevents field names with dot notation being quoted!
		if (in_array(strtoupper($this->field), $this->db->getReserved())) {
			$field = $this->db->quoteKey($this->field);
		}

		if (!$field) {
			//debug_pre_print_backtrace();
		}
//		debug(__METHOD__, $this->list);
		$content = $field . " IN (" . implode(", ", $this->db->quoteValues($this->list)) . ")";
		return $content;
	}

}
