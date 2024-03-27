<?php

class SysInfo extends AppController
{

	public function render()
	{
		$a = [];
		$a['phpVersion'] = phpversion();
		$a['modRewrite'] = $this->request->apacheModuleRewrite();
		$a['variables_order'] = ini_get('variables_order');
		$a['post_max_size'] = ini_get('post_max_size');
		$a['upload_max_filesize'] = ini_get('upload_max_filesize');
		$content[] = $this->encloseInAA(slTable::showAssoc($a), 'Sys Info');

		$config = get_object_vars($this->config);
		$config = array_filter($config, function ($el) {
			if (is_object($el)) {
				return method_exists($el, '__toString');
			} else {
				return true;
			}
		});
		$content[] = $this->encloseInAA(slTable::showAssoc($config), 'Config');

		$content[] = $this->encloseInAA(slTable::showAssoc($_GET), '$_GET');
		$content[] = $this->encloseInAA(slTable::showAssoc($_POST), '$_POST');
		$content[] = $this->encloseInAA(slTable::showAssoc($_COOKIE), '$_COOKIE');
		$content[] = $this->encloseInAA(slTable::showAssoc($_ENV), '$_ENV');
		ksort($_SERVER);
		$content[] = $this->encloseInAA(slTable::showAssoc($_SERVER), '$_SERVER');
		return $content;
	}

}
