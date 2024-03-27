<?php

class PHPInfo extends AppControllerBE
{

//	public $layout = '';

	public function render()
	{
		ob_start();
		phpinfo();
		$content = ob_get_clean();
		$content = preg_replace('/<style type="text\/css">(.*)<\/style>/s', '', $content);
		$content = $this->encloseInAA($content, 'PHP Info');
		return $content;
	}

}
