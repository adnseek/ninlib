<?php

class Selectable
{

	public $name;

	/**
	 * Assoc [14 => 'Koopa']
	 * @var array
	 */
	public $options = [];

	/**
	 * @var int
	 * @public to be visible in debug
	 */
	public $selected;

	/**
	 * @var array of all possible data from DB
	 */
	protected $rows;

	/**
	 * @var array of a single record from $this->rows
	 */
	public $data;

	/**
	 * @param $selected - id / array row
	 */
	public function __construct($selected)
	{
		$this->name = get_class($this);
		if (is_array($selected)) {
			$id = $selected['id'];
			if ($id) {
				$this->rows[$id] = $selected;
				$this->options[$id] = $selected['title'];
				$this->selected = $id;
			}
		} else {
			$this->selected = $selected;
		}
	}

	public function validateSelected()
	{
		// it's called AFTER subclass initialized $this->data
		//debug($selected, array_keys($this->data));
		if (!in_array($this->selected, array_keys($this->options))) {
			/*throw new Exception('Invalid selected ('.$selected.') in '.get_class($this).'<br>
				<li>'.implode('<li>', array_keys($this->data)));
			 *
			 */
			$this->selected = current(array_keys($this->options));
		}
	}

	/**
	 * It's not more convenient to have it in a toString()
	 * @return string
	 */
	public function __toString()
	{
		return $this->getDropdown();
	}

	public function getDropdown()
	{
		$request = Request::getInstance();

		$f = new HTMLForm();
		$f->method('GET');

		$nameless = $request->getURLLevel(0);
		//debug($nameless);
		if (!$nameless) {
			if (in_array($request->getControllerString(), [
				'RoomView',
				'EditRoomDescription',
			])) {
				$f->hidden('c', 'RoomView');
			} else {
				$f->hidden('c', $request->getControllerString());
			}
		}

		$f->selection($this->name, $this->options, $this->selected, true);
		return $f->getContent();
	}

	public function getOptions()
	{
		return $this->options;
	}

	public function getName()
	{
		if (!isset($this->options[$this->selected])) {
			//debug($this->data);
			//debug_pre_print_backtrace();
			return 'Unknown room/location #' . $this->selected;
		}
		return $this->options[$this->selected];
	}

	public function getID()
	{
		return $this->selected;
	}

}
