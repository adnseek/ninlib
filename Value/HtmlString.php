<?php

/**
 * Use $content instanceof HtmlString ? $content : htmlspecialchars($content);
 * Update: use htmlString:hsc($content)
 */
class HtmlString implements ToStringable
{
	use DirectDataAccess;

	protected $value = '';

	public function __construct($input, array $props = [])
	{
		if (is_array($input)) {
			$input = implode(PHP_EOL, $input);
		}
		$this->value = $input . '';
		$this->data = $props;
	}

	/**
	 * htmlspecialchars which knows about HtmlString()
	 * @param string $string
	 * @return string|htmlString
	 */
	public static function hsc($string)
	{
		if ($string instanceof self) {
			return $string;
		}

		return htmlspecialchars($string);
	}

	public function replace($one, $two)
	{
		return new HtmlString(
			str_replace($one, $two, $this->value));
	}

	public function cli()
	{
		return trim(strip_tags($this->render()));
	}

	public function render()
	{
		return $this->__toString();
	}

	public function __toString()
	{
		return $this->value . '';
	}
}
