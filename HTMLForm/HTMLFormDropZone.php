<?php

use spidgorny\nadlib\HTTP\URL;

class HTMLFormDropZone extends HTMLFormType implements HTMLFormFieldInterface
{

	public $makeFallback = true;

	public $class = 'dropzone';

	/**
	 * Shows the form element in the form
	 * @return mixed
	 * @throws Exception
	 */
	public function render()
	{
		$content = [];
		if (!$this->form) {
			$this->form = new HTMLForm();
		}
		$this->form->action(new URL(null, [
			'action' => 'upload',
		]));
		$this->form->formMore['class'] .= ' ' . $this->class;
		$index = Index::getInstance();
		$index->addJS('vendor/enyo/dropzone/dist/min/dropzone.min.js');
		$index->addCSS('vendor/enyo/dropzone/dist/min/dropzone.min.css');

		if ($this->makeFallback) {
			//$u = new Uploader();
			//$form = $u->getUploadForm();
			$form = new HTMLForm();
			$form->text('<div class="fallback">');
			$form->file('file');
			$form->text('</div>');
			$content[] = $form->getBuffer();
		}
		return $content;
	}

}
