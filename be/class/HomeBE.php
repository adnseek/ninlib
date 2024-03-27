<?php

class HomeBE extends AppControllerBE
{

	public static $public = true;

	public function __construct()
	{
		parent::__construct();
	}

	public function render()
	{
		$content = '';
		$content .= new MarkdownView('Home.md');

		//$connection = ssh2_connect('kreuzfahrt-auswahl.de', 22);
		//$auth_methods = ssh2_auth_none($connection, 'ec2-user');
		//debug($auth_methods);

		$cmd = 'hg log -l1';
		@exec($cmd, $output);
		if ($output) {
			$content .= implode('<br />', $output);
		}

		//$content .= getDebug(AutoLoad::getInstance()->getDebug());

		$content .= '<h1>$_ENV</h1>' . getDebug($_ENV);

		return $content;
	}

	public function sidebar()
	{
		$content = SysInfo::getInstance()->render();
		return $content;
	}

}
