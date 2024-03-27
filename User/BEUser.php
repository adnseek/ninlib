<?php

/**
 * UserBase not User because it's not dependent on the main app login system
 */
class BEUser extends UserBase
{

	/**
	 * Loaded from config.json
	 * @var array
	 */
	public $acl = [];

	public function __construct($id = null)
	{
		parent::__construct($id);
		if (class_exists('Config')) {
			Config::getInstance()->mergeConfig($this);
		}
	}

	public function try2login($user, $password = null)
	{
		//debug('session_start');
		if (session_status() != PHP_SESSION_ACTIVE && !Request::isCLI() && !headers_sent()) {
			llog('session_start in BEUser');
			session_start();
		}
	}

	public function can($something)
	{
		return $this->acl[$something];
	}

	public function saveLogin()
	{
		$_SESSION[__CLASS__]['login'] = $this->id;
	}

	public function isAuth()
	{
		return isset($_SESSION[__CLASS__]['login']) && ($_SESSION[__CLASS__]['login'] == $this->id);
	}

	public function logout()
	{
		unset($_SESSION[__CLASS__]['login']);
	}

	public function __destruct()
	{
		// do nothing
	}

	public function isAdmin()
	{
		return true;
	}

	public function getLogin()
	{
		return 'Nadlib Admin';
	}

	public function getAvatarURL()
	{
		return null;
	}

	public function prefs()
	{
		// TODO: Implement prefs() method.
	}

	public function getAllSettings()
	{
		// TODO: Implement getAllSettings() method.
	}

	public function getGroup()
	{
		return null;
	}

	public function getSetting($key)
	{
		// TODO: Implement getSetting() method.
	}
}
