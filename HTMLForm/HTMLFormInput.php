<?php

/**
 * @property mixed $prefix
 * @property string $label
 * @property string $value
 * @property string $type
 * @property string $placeholder
 */
class HTMLFormInput extends HTMLFormType
{

	public $prefix;
	public $label;
	public $name;
	public $placeholder;
	public $value;
	public $type = 'text';
	public $attr = [];

	public function __construct($name, array $attr = ['type' => 'text'])
	{
//		parent::__construct($attr + ['type' => 'text'], $name);
//		$this->name = $name;
//		$this->assign($attr);
		$this->setValue($this->value);
		$this->name = $name;
		$this->attr = $attr;
	}

	/**
	 * @param array $attr
	 * @deprecated
	 */
	public function assign(array $attr)
	{
		array_walk($attr, function ($value, $key) {
			/** @noinspection PhpVariableVariableInspection */
			$this->$key = $value;
		});
	}

	public function __toString()
	{
		return MergedContent::mergeStringArrayRecursive($this->render());
	}

	public function render()
	{
		$input = new HTMLTag('input', [
			'type' => $this->type,
			'name' => $this->form->getName($this->name, '', true),
			'placeholder' => $this->placeholder,
			'value' => $this->value,
		]);
		return MergedContent::mergeStringArrayRecursive($input);
	}

	public function getContent()
	{
		return $this->render();
	}

}
