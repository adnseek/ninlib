<?php

class HTMLFormTimeRange extends HTMLFormType
{
	public $div = '1';
	public $min = 0;
	public $max = 1440;        // 24*60

	/**
	 * @var IndTime
	 */
	public $start; // = 1000;

	/**
	 * @var IndTime
	 */
	public $end; // = 1730;

	public $step = 30;

	/**
	 * @param string $field
	 * @param array $value - array of minutes
	 */
	public function __construct($field, array $value)
	{
//		parent::__construct();
		$this->field = $field;
		if (is_array($value) && sizeof($value) == 2) {
			list($this->start, $this->end) = $value;
		}
		$this->div = uniqid();

		// to load libs in the NON-AJAX page request
		Index::getInstance()->addJQueryUI();
		$al = AutoLoad::getInstance();
		Index::getInstance()->addJS($al->nadlibFromDocRoot . 'HTMLForm/HTMLFormTimeRange.js');
	}

	/**
	 * It's a string value which needs to be parsed into the minutes!!!
	 *
	 * @param string $value - 10:00-13:30
	 */
	public function setValue($value)
	{
		if ($value) {
			list($this->start, $this->end) = $this->parseRange($value);
		}
	}

	/**
	 * @param $value
	 * @return array[IndTime, IndTime]
	 * @throws Exception
	 */
	public static function parseRange($value)
	{
		if (strlen($value) == 11) {
			$parts = explode('-', $value);
			if (sizeof($parts) == 2) {
				$s = new Time($parts[0]);
				$e = new Time($parts[1]);
			} else {
				throw new Exception('Unable to parse time range: ' . $value);
			}
		} else {
			throw new Exception('Unable to parse time range: ' . $value);
		}
		return [$s, $e];
	}

	public function render()
	{
		assert($this->step);
		$al = AutoLoad::getInstance();
		$content = new View($al->nadlibRoot . 'HTMLForm/HTMLFormTimeRange.phtml', $this);
		$fieldString = $this->form->getName($this->field, '', true);
		$fieldString = str_replace('[', '\\[', $fieldString);
		$fieldString = str_replace(']', '\\]', $fieldString);
		$content->fieldEscaped = $fieldString;
		return $content;
	}

}
