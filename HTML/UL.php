<?php

class UL implements ToStringable
{

	public $items = [];

	public $before = '<ul>';

	public $after = '</ul>';

	public $wrap = '<li ###ACTIVE###>|</li>';

	/**
	 * Should be equal to an $this->items key which is selected
	 * @var string
	 */
	public $activeClass = '';

	/**
	 * Piece of HTML to mark active items
	 * @var string
	 */
	public $active = ' class="active"';

	public $links = [];

	/**
	 * <a href="###LINK###">|</a>
	 * @var string
	 */
	public $linkWrap = '';

	/**
	 * @var Closure callback to link generation function(index, name)
	 */
	public $linkFunc;

	public function __construct(array $items = [])
	{
		$this->items = $items;
		$this->activeClass = key($this->items);
	}

	public function add($value, $key = null)
	{
		if ($key) {
			$this->items[$key] = $value;
		} else {
			$this->items[] = $value;
		}
	}

	public function makeClickable($urlPrefix = '')
	{
		$this->linkWrap = '<a href="' . $urlPrefix . '###LINK###">|</a>';
		$this->links = array_keys($this->items);
		$this->links = array_combine($this->links, $this->links);
	}

	public function render()
	{
		$out = $this->withoutUL();
		$content = $this->before . implode("\n", $out) . $this->after;
		return $content;
	}

	public function withoutUL()
	{
		$out = [];
		foreach ($this->items as $class => $li) {
			$link = $this->getLinkFor($class, $li);

			// maybe we need to wrap after $this->links
			if ($this->linkWrap && !is_numeric($class)) {
				$wrap = Wrap::make($this->linkWrap);
				// don't translate __() because the values may come from DB
				if (is_array($li)) {
					$li = MergedContent::mergeStringArrayRecursive($li);
				}
				$li = $wrap->wrap($li);
			} else {
				$link = null;
			}

			$line = Wrap::make($this->wrap)->wrap($li);
			if ($link) {
				$line = str_replace('%23%23%23LINK%23%23%23', $link, $line);
				$line = str_replace('###LINK###', $link, $line);
			}
			$line = str_replace('###CLASS###', $class, $line);
			$line = str_replace('###TEXT###', $li, $line);
			$line = str_replace('###ACTIVE###', $class == $this->activeClass ? $this->active : '', $line);
			$out[] = $line;
		}
		return $out;
	}

	public function __toString()
	{
		return $this->render();
	}

	public static function DL(array $assoc)
	{
		$ul = new UL($assoc);
		$links = array_keys($assoc);
		$ul->links = array_combine($links, $links);
		$ul->wrap = '<dt>###CLASS###</dt>|';
		$ul->linkWrap = '<dd>|</dd>';
		$ul->before = '<dl class="dl-horizontal">';
		$ul->after = '</dl>';
		return $ul;
	}

	public static function recursive(array $epesEmployees)
	{
		foreach ($epesEmployees as &$el) {
			if ($el instanceof Recursive) {
				$el = $el->value . UL::recursive($el->getChildren());
			}
		}
		$ul = new UL($epesEmployees);
		return $ul;
	}

	public function cli()
	{
		foreach ($this->items as $class => $li) {
			echo '* ', strip_tags($li);
			if (!is_numeric($class)) {
				echo ' [', $class, ']', BR;
			} else {
				echo BR;
			}
		}
	}

	/**
	 * @param $class
	 * @param $li
	 * @return mixed
	 */
	protected function getLinkFor($class, $li)
	{
		if ($this->links) {
			$link = $this->links[$class];
		} elseif ($this->linkFunc) {
			$link = call_user_func($this->linkFunc, $class, $li);
		} else {
			$link = $class;
		}
		return $link;
	}

	public function clear()
	{
		$this->before = '';
		$this->after = '';
		$this->wrap = '|';
	}

}
