<?php

class AnyPage /*extends AppController */
{

	/**
	 * @var Path
	 */
	public $folder;

	/**
	 * @var Request
	 */
	protected $request;

	/**
	 * @var string
	 */
	protected $template;

	public $layout = '';

	public function __construct($folder)
	{
		$this->folder = new Path($folder);
		$this->request = Request::getInstance();
	}

	public function detect()
	{
		$file = $this->request->getPathAfterDocRoot()->basename();
		//debug($file);
		$mask = cap($this->folder) . $file . '.*';
		$files = glob($mask);
		//debug($mask, $files);
		if ($files) {
			$this->template = $files[0];
			return true;
		}
		return false;
	}

	public function render()
	{
		$this->request->set('ajax', true);
		$v = new View($this->template, $this);
		$v->baseHref = $this->request->getLocation();
		$html = $v->render();
		$html = str_replace('<!-- base.href -->', '<base href="dev-jobz/" />', $html);
//		debug(substr($html, 0, 256));
//		exit;
		return $html;
	}

}
