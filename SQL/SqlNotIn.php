<?php

/**
 * Class SQLNotIn *
 */
class SQLNotIn extends SQLWherePart
{

	public $list = [];

	/**
	 * SQLNotIn constructor.
	 * @param array $list
	 */
	public function __construct(array $list)
	{
		parent::__construct();
		$this->list = $list;
	}

	/**
	 * @return string
	 */
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
		$content = $field . " NOT IN (" . implode(", ", $this->db->quoteValues($this->list)) . ")";
//		debug($content); die;
		return $content;
	}

}
