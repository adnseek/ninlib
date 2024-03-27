<?php

class HTMLFormDatePicker extends HTMLFormType implements HTMLFormTypeInterface
{
	/**
	 * PHP Format
	 * @var string
	 */
	public $format = 'Y-m-d';

	/**
	 * JS format
	 * @var string
	 */
	public $jsFormat = 'yy-mm-dd';

	public $jsParams = [];

	public $inputType = 'date';

	public $content;

	/**
	 *
	 */
	public function __construct()
	{
		$index = Index::getInstance();
		$index->addJQueryUI();    // for the picker
		$index->addJS(AutoLoad::getInstance()->nadlibFromDocRoot . 'js/HTMLFormDatePicker.js');
	}

	public function render()
	{
		$tmp = $this->form->stdout;
		$this->form->stdout = '';
//
//		echo __METHOD__, BR;
		//debug($this->field, $this->value);
		if ($this->value && $this->value != '0000-00-00') {
			if (is_integer($this->value) || is_numeric($this->value)) {
				$val = date($this->format, $this->value);
			} else {
				$val = strtotime($this->value);    // hope for Y-m-d
				$val = date($this->format, $val);
			}
		} else {
			$val = '';
		}
		$this->form->input($this->field, $val, [
				'format' => $this->jsFormat
			] + $this->jsParams,
			$this->inputType, ifsetor($this->desc['class']) . ' datepicker');

		$this->content = $this->form->stdout;
		$this->form->stdout = $tmp;
		return $this->content;
	}

	/**
	 * Convert to timestamp using the supplied format
	 * @param $value
	 * @return int
	 */
	public function getISODate($value)
	{
		//debug($value, is_integer($value), is_numeric($value), strtotime($value));
		if ($value && (is_integer($value) || is_numeric($value))) {
			$val = intval($value);
		} elseif ($value && is_string($value) && $this->jsFormat == 'dd.mm.yy') {
			$val = explode('.', $value);
			$val = array_reverse($val);
			$val = implode('-', $val);
			$val = strtotime($val);
		} elseif ($value) {
			$val = $value;
			$val = strtotime($val);
		} else {
			$val = null;    // time();
		}
		//debug($this->jsFormat, $value, $val);
		return $val;
	}

	public function setValue($value)
	{
		//debug(__METHOD__, $this->field, $value);
		parent::setValue($value);
	}

	public function getContent()
	{
//		echo __METHOD__, BR;
		return $this->render();
	}

}
