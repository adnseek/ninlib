<?php

/**
 * Documentation for users
 * TYPO3User -> User (app) -> UserBase -> OODBase
 * TYPO3User -> UserBase -> OODBase
 * PlainSessionUser -> User (app) -> UserBase -> OODBase
 * SessionUser -> PlainSessionUser -> User (app) -> UserBase -> OODBase
 */

class TYPO3Module extends UserBase
{

	protected $module;

	/**
	 * @v a r \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	public $t3user;

	public function __construct($module)
	{
		parent::__construct();
		$this->module = $module;
		$this->t3user = $GLOBALS['BE_USER'];
	}

	public function getPref($key)
	{
		//d($this->t3user->uc);
		return $this->t3user->uc['moduleData'][$this->module][$key];
	}

	public function setPref($key, $val)
	{
		$this->t3user->uc['moduleData'][$this->module][$key] = $val;
		$this->t3user->pushModuleData($this->module, $this->t3user->uc['moduleData'][$this->module], false);
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
