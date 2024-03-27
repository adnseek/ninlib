<?php

class HTMLFormMonthYear extends HTMLFormType
{

	public $months = [
		null => 'месяц', 1 =>
			'Jan', 'Feb', 'Mar',
		'Apr', 'May', 'Jun',
		'Jul', 'Aug', 'Sep',
		'Oct', 'Nov', 'Dec'];

	public $selMonth;

	public $year;

	/**
	 * @param string $field
	 */
	public function __construct($field)
	{
//		parent::__construct();
		$this->field = $field;
	}

	public function render()
	{
		if (!$this->form) {
			debug_pre_print_backtrace();
		}
		$fieldStringM = $this->form->getNameField(array_merge($this->field, ['month']), '', true);
		$fieldStringY = $this->form->getNameField(array_merge($this->field, ['year']), '', true);
		$content[] = '<table class="' . __CLASS__ . '"><tr><td>';
		$content[] = '<select name="' . $fieldStringM . '" class="form-control">';
		$content[] = $this->showMonthOptions();
		$content[] = '</select>';
		$content[] = '</td><td style="padding-left: 1em;">';
		$content[] = '<input type="number"
			name="' . $fieldStringY . '"
			value="' . $this->year . '"
			size="5"
			placeholder="год"
			class="form-control"
			min="1900" max="2100"/>';
		$content[] = '</td></tr></table>';
		return $content;
	}

	public function setValue($value)
	{
		$this->selMonth = $value['month'];
		$this->year = $value['year'];
	}

	public function validate()
	{
		return $this->year ? null : 'Введите год';
	}

	public function showMonthOptions()
	{
		$content = [];
		foreach ($this->months as $m => $mon) {
			$content[] = new HTMLTag('option', [
					'value' => $m,
				] + ($this->selMonth == $m ? ['selected' => true] : []),
				$mon);
		}
		return $content;
	}

}
