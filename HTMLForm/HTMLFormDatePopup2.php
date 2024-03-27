<?php

class HTMLFormDatePopup2 extends HTMLFormType
{
	/**
	 * @var $form HTMLForm
	 */
	public $form;

	protected $name;

	/**
	 * @var int timestamp
	 */
	public $value;

	public $desc;

	public $id;

	public function __construct(HTMLForm $form, $name, $value, array $desc = [
		'phpFormat' => 'Y-m-d',
	])
	{
		$this->form = $form;
		$this->name = $name;

		$this->setValue($value);

		$this->desc = $desc;
		$this->id = uniqid();
	}

	public function setValue($value)
	{
		if ($value instanceof Time) {
			$this->value = $value->getTimestamp();
		} elseif (is_string($value)) {
			$parse = strtotime($value);
			if (-1 != $parse) {
				$this->value = $parse;
			} else {
				throw new InvalidArgumentException('[' . $value . '] is a date?');
			}
		} else {
			$this->value = $value;
		}
//		debug(__METHOD__, $value, $this->value);
	}

	public function render()
	{
		$fullname = $this->form->getName($this->name, '', true);
		$printValue = $this->value
			? date($this->desc['phpFormat'] ?: 'Y-m-d', $this->value)
			: '';
		$Ymd = $this->value
			? date('Ymd', $this->value)
			: date('Ymd');
		$GLOBALS['HTMLHEADER']['datepopup'] = '
			<link rel="stylesheet" type="text/css" href="lib/JSCal2-1.7/src/css/jscal2.css" />
			<link rel="stylesheet" type="text/css" href="lib/JSCal2-1.7/src/css/border-radius.css" />
			<script type="text/javascript" src="lib/JSCal2-1.7/src/js/jscal2.js"></script>
			<script type="text/javascript" src="lib/JSCal2-1.7/src/js/lang/en.js"></script>';
		$content = '
			<input type="text" name="' . $fullname . '" id="id_field_' . $this->id . '" value="' . $printValue . '" class="datepopup2"/>
			<button type="button" id="id_button_' . $this->id . '">...</button>
			<script type="text/javascript">
			    Calendar.setup({
			        inputField:		"id_field_' . $this->id . '",		// id of the input field
			        trigger:		"id_button_' . $this->id . '",   	// trigger for the calendar (button ID)
					weekNumbers:	true,
					onSelect:		function() { this.hide() },
					//min: 			Calendar.dateToInt(new Date),
					//date:			Calendar.dateToInt(new Date(' . $this->value . '*1000)),
					date:			' . $Ymd . ',
					selection:		Calendar.dateToInt(new Date(' . intval($this->value) . '*1000)),
					align:			"Bc/Bc/Bc/Bc/Bc"' .
			$this->desc['plusConfig'] . '
			    });
			</script>';
		return $content;
	}

	public function __toString()
	{
		return $this->render() . '';
	}

}
