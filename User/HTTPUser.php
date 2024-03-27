<?php

class HTTPUser extends UserBase
{

	protected $login;
	protected $password;

	public function __construct()
	{
		$this->login = $_SERVER['PHP_AUTH_USER'];
		$this->password = $_SERVER['PHP_AUTH_PASSWORD'];
	}

	public function __toString()
	{
		return $this->login;
	}

	/**
	 * @param string $login
	 * @param string $email
	 * @return mixed
	 */
	public function try2login($login, $email = null)
	{
		// TODO: Implement try2login() method.
	}

	public function isAdmin()
	{
		// TODO: Implement isAdmin() method.
	}

	public function getLogin()
	{
		// TODO: Implement getLogin() method.
	}

	public function getAvatarURL()
	{
		// TODO: Implement getAvatarURL() method.
	}

	/**
	 * @param string $acl
	 * @return bool
	 */
	public function can($acl)
	{
		return false;
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
