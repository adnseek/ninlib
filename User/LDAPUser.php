<?php

/**
 * Class LDAPUser
 * @see LDAPLogin
 */
abstract class LDAPUser extends UserBase implements UserModelInterface
{

	public $UserID;
	public $UserName;

	/**
	 * @var array
	 */
	public $data;

	public function __construct(array $ldapInfo = [])
	{
		$this->initLDAP($ldapInfo);
	}

	public function initLDAP(array $ldapInfo = [])
	{
		$goodKeys = array_filter(array_keys($ldapInfo), 'is_string');
		$ldapInfo = array_intersect_key($ldapInfo, array_flip($goodKeys));
		$this->data = $ldapInfo;
		if (isset($this->data['uid'])) {
			$this->id = $this->data['uid'][0];
			$this->UserID = $this->data['uid'][0];
		}
		if (isset($this->data['fullname'])) {
			$this->UserName = $this->data['fullname'][0];
		}
	}

	public function getName()
	{
		return $this->UserName . ' (' . $this->UserID . ') <' . $this->data['mail'][0] . '>';
	}

	public function try2login($user, $password = null)
	{
		if ($_SESSION['user']) {
			$this->id = $_SESSION['user']->id;
			$this->data = $_SESSION['user']->data;
			$this->UserID = $_SESSION['user']->UserID;
			$this->UserName = $_SESSION['user']->UserName;
		}
	}

	public function saveLogin()
	{
		$obj = new stdClass();
		$obj->id = $this->id;
		$obj->data = $this->data;
		$obj->UserID = $this->UserID;
		$obj->UserName = $this->UserName;
		$_SESSION['user'] = $obj;
	}

	public function logout()
	{
		unset($_SESSION['user']);
	}

	/**
	 * Simplifies $this->data for display
	 * @return array
	 */
	public function getInfo()
	{
		$simpleData = [];
		foreach ($this->data as $field => $data) {
			if (is_array($data) && $data['count'] == 1) {
				$simpleData[$field] = $data[0];
			} elseif (is_array($data)) {
				unset($data['count']);
				$simpleData[$field] = $data;
			}
		}
		unset($simpleData['zcmsharedsecret']);
		unset($simpleData['zenzfdversion']);
		unset($simpleData['dirxml-passwordsyncstatus']);
		unset($simpleData['protocom-sso-security-prefs']);
		unset($simpleData['protocom-sso-entries']);
		unset($simpleData['nnmclientsettings']);
		unset($simpleData['nnmcontactlist']);
		unset($simpleData['sasloginsecret']);
		return $simpleData;
	}

	public function getEmail()
	{
		return ifsetor($this->data['mail'][0]);
	}

	public function getPasswordHash()
	{
		return $this->data['lastlogontimestamp'][0];
	}

}
