<?php

class ViewTemplate extends AppController
{

	public function sidebar()
	{
		$folder = AutoLoad::getInstance()->appRoot . 'template';
		$content[] = $folder . BR;
		$files = new ListFilesIn($folder);
		$ul = new UL($files->getArrayCopy());
		$content[] = $ul;
		return $content;
	}

	public function render()
	{
		$content[] = '';
		return $content;
	}

}
