<?php

class HTMLFormRange extends HTMLFormType
{

	public $min = 0;

	public $max = 1440;    // 24*60

	public $value;

	public $step = 1;

	/**
	 * @var AutoLoad
	 */
	public $al;

	public $jsFile;

	/**
	 * @param string $field
	 * @param int $value
	 */
	public function __construct($field, $value = 0)
	{
//		parent::__construct();
		$this->field = $field;
		$this->setValue($value);
		$this->al = AutoLoad::getInstance();
	}

	/**
	 * @param int $value
	 * @return mixed|void
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}

	public function render()
	{
		$index = Index::getInstance();
		$index->addJQuery();
		$index->addJS($this->jsFile
			?: $this->al->nadlibFromDocRoot . 'HTMLForm/HTMLFormRange.js');

		$view = View::getInstance($this->al->nadlibRoot . 'HTMLForm/HTMLFormRange.phtml', $this);
		$fieldString = $this->form->getName($this->field, '', true);
		$fieldString = str_replace('[', '\\[', $fieldString);
		$fieldString = str_replace(']', '\\]', $fieldString);
		$view->fieldEscaped = $fieldString;
		return $view->render();
	}

}
