<?php

/**
 * @property mixed $prefix
 * @property string $label
 * @property string $name
 */
class HTMLFormLabelInput extends HTMLFormInput
{

	public function render()
	{
		$label = new HTMLTag('label', [], $this->label);
		$input = new HTMLTag('input', [
			'type' => $this->type,
			'name' => $this->form->getName($this->name, '', true),
			'placeholder' => $this->placeholder,
			'value' => $this->value,
		]);
		return MergedContent::mergeStringArrayRecursive([$label, $input]);
	}

}
