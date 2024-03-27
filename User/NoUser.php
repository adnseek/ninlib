<?php

/**
 * Some places require a user object which does nothing if you're not logged-in
 */
class NoUser extends UserBase implements UserModelInterface
{

	/**
	 * @var Preferences|MockPreferences
	 */
	public $prefs;

	/**
	 * @var AccessRightsInterface
	 */
	public $access = [];

	public function __construct()
	{
		$this->prefs = new MockPreferences($this);
	}

	public function setAccess($name, $value)
	{
		$this->access[$name] = $value;
	}

	public function can($name)
	{
		return $this->access[$name];
	}

	public function renderMessages()
	{
		return '';
	}

	public function getPref($key)
	{
		return $this->prefs->get($key);
	}

	public function setPref($key, $val)
	{
		$this->prefs->set($key, $val);
	}

	/**
	 * @return null
	 */
	public function getUnreadMessages()
	{
		return null;
	}

	public function getAllSettings()
	{
		return [];
	}

	public function getSelfAndBackupID()
	{
		return [$this->id];
	}

	public function getAllSubordinates()
	{
		return [];
	}

	public function try2login($user, $password = null)
	{
	}

	public function isAdmin()
	{
		return false;
	}

	public function getLogin()
	{
		return 'nobody';
	}

	public function getAvatarURL()
	{
		return 'http://avatar.com/';
	}

	public function prefs()
	{
		return $this->prefs;
	}

	public function getAllPrefs()
	{
		return $this->prefs()->getData();
	}

	public function getPerson()
	{
		return null;
	}

	public function isDev()
	{
		return false;
	}

	public function getGroup()
	{
		// TODO: Implement getGroup() method.
	}

	public function loginFromHTTP()
	{
		// do nothing, we failed to login with a session
	}

	public function getSetting($key)
	{
		return null;
	}
}
