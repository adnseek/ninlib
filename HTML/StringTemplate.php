<?php

/**
 * Class StringTemplate - renders PHP template with {$variable} substitution
 */
class StringTemplate
{
	public $filename;
	protected $content;
	protected $lines;
	protected $caller;

	public function __construct($file, $self = null)
	{
		$this->filename = $file;
		$filepath = Path::isItAbsolute($file) ? $file : 'template/' . $file;
		if (!file_exists($filepath)) {
			throw new RuntimeException($filepath . ' does not exist.');
		}
		$this->content = file_get_contents($filepath);
		$this->caller = $self;
	}

	/**
	 * Made in order to extract the subject line from the template
	 *
	 * @param int $line
	 * @return string
	 */
	public function getLine($line)
	{
		$this->lines = explode("\n", $this->content);
		$content = $this->lines[$line];
		unset($this->lines[$line]);
		return $content;
	}

	/**
	 * In relation to the getLine();
	 *
	 * @return string
	 */
	public function getRest()
	{
		return implode("\n", $this->lines);
	}

	public function __toString()
	{
		return $this->render();
	}

	public function render()
	{
		return eval("return<<<END\n" . $this->content . "\nEND;\n"); // space is important
	}

	public function __call($func, array $args)
	{
		$method = [$this->caller, $func];
		if (!is_callable($method) || !method_exists($this->caller, $func)) {
			$method = ['Controller', $func];
		}
		return call_user_func_array($method, $args);
	}

	public function &__get($var)
	{
		return $this->caller->$var;
	}

}
