<?php

/**
 * Documentation for users
 * TYPO3User -> User (app) -> UserBase -> OODBase
 * TYPO3User -> UserBase -> OODBase
 * PlainSessionUser -> User (app) -> UserBase -> OODBase
 * SessionUser -> PlainSessionUser -> User (app) -> UserBase -> OODBase
 */

class TYPO3User extends UserBase
{

	/**
	 * @var array
	 */
	public $t3user;

	public function __construct($id = null)
	{
		parent::__construct($id);
		$this->t3user = $GLOBALS["TSFE"]->fe_user;    // set to be_user if you need
	}

	public function getPref($key)
	{
		return $this->t3user->getKey('user', $key);
	}

	public function setPref($key, $val)
	{
		$this->t3user->setKey('user', $key, $val);
		$this->t3user->storeSessionData();
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
