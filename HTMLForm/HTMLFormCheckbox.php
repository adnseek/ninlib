<?php

class HTMLFormCheckbox extends HTMLFormType implements HTMLFormTypeInterface
{

	public $checked;

	public function __construct($name, $checked, array $more = [])
	{
		$this->field = $name;
		$this->checked = $checked;
		$this->desc = $more;
		if (isset($more['value'])) {
			$this->setValue($more['value']);
		}
	}

	public function render()
	{
		$this->form = $this->form instanceof HTMLForm
			? $this->form
			: new HTMLForm();
		//$value = htmlspecialchars($value, ENT_QUOTES);
		//$this->stdout .= "<input type=checkbox ".$this->getName($name)." ".($checked?"checked":"")." value=\"$value\" $more>";
		$more = $this->desc['more'];
		if (ifsetor($this->desc['elementID'])) {
			//$more['id'] = $this->desc['elementID'];
		}
		//debug($this->field, $this->value, $this->desc);
//		llog($this->desc);
		$more =
			($this->checked ? ['checked' => "checked"] : [])
			+ ($this->desc['autoSubmit'] ? ["onchange" => "this.form.submit()"] : [])
			+ (ifsetor($this->desc['required']) ? ["required" => true] : [])
			+ $more;
		return $this->form->getInput("checkbox", $this->field, $this->value, $more, ifsetor($more['class']));
	}

}
