<?php

class HTMLSubmit implements HTMLFormFieldInterface
{

	protected $value;

	protected $params = [];

	/** @var HTMLForm */
	protected $form;

	protected $field;

	public function __construct($value = '', array $params = [])
	{
		$this->value = $value;
		$this->params = $params;
		$this->field = ifsetor($params['name'], 'btnSubmit');
	}

	/**
	 * Shows the form element in the form
	 * @return mixed
	 */
	public function render()
	{
		$params = $this->params;
		$params['class'] = ifsetor($params['class'], 'submit btn');
		$params['name'] = ifsetor($params['name'], 'btnSubmit');
		//$value = htmlspecialchars(strip_tags($value), ENT_QUOTES);
		//$this->stdout .= "<input type=\"submit\" ".$this->getAttrHTML($params)." ".($value?'value="'.$value.'"':"") . " $more />\n";
		// this.form.submit() will not work
		//debug('submit', $params);
		$content = $this->form->getInput("submit", $this->field, $this->value, $params, $params['class']);
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
