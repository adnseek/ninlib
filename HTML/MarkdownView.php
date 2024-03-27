<?php

/**
 * Class MarkdownView
 * composer require michelf/php-markdown
 */
class MarkdownView extends View
{

	/**
	 * Cached processed markdown to HTML
	 * @var string
	 */
	var $content;

	function loadTemplate()
	{
		$file = dirname($this->file) != '.'
			? $this->file
			: $this->folder . $this->file;
		$contents = file_get_contents($file);

		// with autoloader from composer this should not be necessary
		//include_once dirname(__FILE__) . '/../vendor/michelf/php-markdown/Michelf/Markdown.inc.php';
		if (class_exists('\Michelf\Markdown')) {
			$html = Michelf\Markdown::defaultTransform($contents);
		} else {
			$this->index->error('Markdown class in not installed');
			$html = $contents;
		}
		$this->content = $html;
	}

	/**
	 * $vars are not used
	 */
	function render(array $vars = [])
	{
		if (!$this->content) {
			$this->loadTemplate();
		}
		return $this->content;
	}

	public function processIncludes()
	{
		$content = $this->render();
		$content = preg_replace_callback('/{{(.+?)}}/', function ($matches) {
			return (new MarkdownView($matches[1]))->render();
		}, $content);
		$this->content = $content;
	}

	function twig($placeholder, $content)
	{
		$this->render();    // load template first
		$this->content = str_replace('{{' . $placeholder . '}}', $this->s($content), $this->content);
		return $this;
	}

}
