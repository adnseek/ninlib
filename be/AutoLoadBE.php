<?php

class AutoLoadBE extends AutoLoad
{

	/**
	 * @var AutoLoad
	 */
	private static $instance2;

	public function getFolders()
	{
		require_once __DIR__ . '/../HTTP/Request.php';
		$folders = [];
		if (!Request::isCLI()) {
			if ($this->useCookies) {
				//debug('session_start', $this->nadlibFromDocRoot);
				session_set_cookie_params(0, '');    // current folder
				session_start();

				if (isset($_SESSION[__CLASS__])) {
					$folders = $_SESSION[__CLASS__]['folders'] ?? [];
					$this->classFileMap = $_SESSION[__CLASS__]['classFileMap'] ?? [];
				}
			}
		}

		if (!$folders) {
			$folders = ['be/class'];
			$folders = array_merge($folders, $this->folders->getFoldersFromConfigBase());
			// should come first to override /be/
			$folders = array_merge($folders, $this->folders->getFoldersFromConfig());
		}
		debug($folders);

		return $folders;
	}

	/**
	 * @return AutoLoad
	 */
	public static function getInstance()
	{
		if (!self::$instance2) {
			self::$instance2 = new self();
		}
		return self::$instance2;
	}

}
