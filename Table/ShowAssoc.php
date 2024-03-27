<?php

class ShowAssoc
{

	public $data = [];

	public $thes = [];

	public $title;

	public function __construct(array $assoc)
	{
		$this->data = $assoc;
	}

	public function setThes(array $thes)
	{
		$this->thes = $thes;
	}

	public function setTitle($title)
	{
		$this->title = $title;
		return $this;
	}

	public function render()
	{
		TaylorProfiler::start(__METHOD__);
		$content[] = '<div class="showAssoc">';
		if ($this->title) {
			$content[] = '<h3>' . ($this->title) . ':</h3>';
		}
		$assoc = [];
		foreach ($this->thes as $key => $name) {
			$val = ifsetor($this->data[$key]);
			if (is_array($name)) {
				$val = $this->getValue($name, $val);
			}
			$niceName = is_array($name) ? $name['name'] : $name;
			$assoc[(string)$niceName] = $val ?: '&nbsp;';
		}
		$content[] = UL::DL($assoc)->render();
		$content[] = '</div>';
		TaylorProfiler::stop(__METHOD__);
		return $content;
	}

	public function getValue(array $desc, $val)
	{
		if (ifsetor($desc['reference'])) {
			// class name
			$class = $desc['reference'];
			$obj = $class::tryGetInstance($val);
			if (method_exists($obj, 'getNameLink')) {
				$val = new HtmlString($obj->getNameLink());
			} elseif (method_exists($obj, 'getName')) {
				$val = $obj->getName();
			} else {
				$val = $obj->__toString();
			}
		} elseif (ifsetor($desc['bool'])) {
			if (ifsetor($desc['t/f'])) {
				$val = $val == 't';
			}
			$val = $desc['bool'][$val];  // yes/no
		} elseif (is_callable(ifsetor($desc['render']))) {
			$val = call_user_func($desc['render'], $this->data, $val);
		} else {
//			$val = $val;
		}
		return $val;
	}

	public function __toString()
	{
		return MergedContent::mergeStringArrayRecursive($this->render());
	}

}
