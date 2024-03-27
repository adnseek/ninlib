<?php

interface UserModelInterface
{

	/**
	 * @param string $login
	 * @param string $email
	 * @return mixed
	 */
	public function try2login($login, $email = null);

	/**
	 * Implementation may vary.
	 * @return mixed
	 */
	/*function saveLogin();*/

	public function isAuth();

	public function isAdmin();

	public function getLogin();

	public function insert(array $data);

	public function getAvatarURL();

	public function prefs();

	/**
	 * @param string $acl
	 * @return bool
	 */
	public function can($acl);

	public function getID();

	public function getAllSettings();

	public function getSetting($key);

	public function getGroup();

//	public function getData();

}
