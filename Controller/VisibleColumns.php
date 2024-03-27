<?php

class VisibleColumns extends ArrayPlus
{

	public function __construct(array $array = [])
	{
		if (!self::has_string_keys($array)) {
			// only keys are provided
			$array = array_fill_keys($array, true);
		}
		parent::__construct($array);
	}

	/**
	 * If there are no data then all columns are visible
	 * @param string $key
	 * @return bool|null
	 */
	public function isVisible($key)
	{
		if (!$this->count()) {
			return true;
		}
		return ifsetor($this[$key]);
	}

	public function getData()
	{
		$data = parent::getData();
		$onlySet = array_filter($data);
		if (!$onlySet) {    // all unset
			$data = array_fill_keys(array_keys($data), true); // all set
		}
		return $data;
	}

}
