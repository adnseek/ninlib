<?php

namespace spidgorny\nadlib;

class Docs extends \AppController
{

	public function render()
	{
		$file = $this->request->getFilename('file');
		$file = $file ?: 'index.md';
		$md = new \MarkdownView(\AutoLoad::getInstance()->nadlibRoot . 'docs/' . $file);
		return $md->render();
	}

}
