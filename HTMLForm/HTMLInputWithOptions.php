<?php

class HTMLInputWithOptions implements HTMLFormFieldInterface
{

	protected $field;

	protected $options = [];

	protected $listName;

	/**
	 * @var HTMLForm
	 */
	protected $form;

	protected $value;

	public function __construct($field, $value, array $options, $listName = 'browsers')
	{
		$this->field = $field;
		$this->value = $value;
		$this->options = $options;
		$this->listName = $listName;
	}

	/**
	 * Shows the form element in the form
	 * @return mixed
	 */
	public function render()
	{
		$content[] = '<input name="' . $this->field . '" 
		list="' . $this->listName . '" 
		value="' . htmlspecialchars($this->value) . '">
		<datalist id="' . $this->listName . '">';
		foreach ($this->options as $option => $description) {
			$content[] = '<option value="' . htmlspecialchars($option) . '">' . htmlspecialchars($description) . '</option>';
		}
		$content[] = '</datalist>';
		return $content;
	}

	/**
	 * Whet's the key name
	 * @param $field
	 * @return mixed
	 */
	public function setField($field)
	{
		$this->field = $field;
	}

	/**
	 * Inject form for additional function calls
	 * @param HTMLForm $form
	 * @return mixed
	 */
	public function setForm(HTMLForm $form)
	{
		$this->form = $form;
	}

	/**
	 * Set current field value
	 * @param $value
	 * @return mixed
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}
}
