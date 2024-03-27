<?php

class LDAPLogin
{

	/**
	 * @var string
	 */
	public $LDAP_HOST;

	/**
	 * @var string
	 */
	public $LDAP_BASEDN;

	public $_ldapconn;

	/**
	 * @var LDAPUser
	 */
	public $data;

	public $error = null;

	/**
	 * @var string LDAPUser::class or a descendant
	 */
	public $userClass = LDAPUser::class;

	public function __construct($host, $base)
	{
		$this->LDAP_HOST = $host;
		$this->LDAP_BASEDN = $base;
	}

	private function _connectLdap()
	{
		if (!$this->_ldapconn) {
			$this->reconnect();
		}
	}

	public function reconnect()
	{
		if ($this->_ldapconn) {
			ldap_unbind($this->_ldapconn);
		}
//		ldap_set_option(null, LDAP_OPT_DEBUG_LEVEL, 7);
		$this->_ldapconn = ldap_connect($this->LDAP_HOST);
		if (!$this->_ldapconn) {
			throw new RuntimeException("Couldn't connect to the LDAP server.");
		}
		// https://stackoverflow.com/questions/17742751/ldap-operations-error
		ldap_set_option($this->_ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($this->_ldapconn, LDAP_OPT_REFERRALS, 0);
	}

	/**
	 * What if the password contains special characters?
	 * @param $string
	 * @return string
	 */
	public function _sanitizeLdap($string)
	{
		return trim(preg_replace('/[^a-zA-Z0-9_]+/', '', $string));
	}

	/**
	 * http://php.net/manual/en/function.ldap-bind.php
	 * @param $loginDN
	 * @param $password
	 * @return mixed
	 */
	public function bind($loginDN, $password)
	{
		$this->_connectLdap();
		return ldap_bind($this->_ldapconn, $loginDN, $password);
	}

	/**
	 * @param $username
	 * @param $password
	 * @return bool|LDAPUser
	 * @throws LoginException
	 */
	public function authLdap($username, $password)
	{
		$this->_connectLdap();

		if (($username == null) || ($password == null)) {
			$this->error = "Fields cannot be blank.";
			return false;
		}

		if ($this->_ldapconn) {
			//$filter = "(&(objectClass=user)(objectCategory=person)(cn=" . $this->_sanitizeLdap($username) . "))";
			$filter = "(&(objectClass=user)(cn=" . $this->_sanitizeLdap($username) . "))";
			//echo $filter;
//			$attributes = ['dn', 'uid', 'fullname', 'givenname', 'firstname'];

			//			debug($this->_ldapconn, $this->LDAP_BASEDN, $filter);
			$search = ldap_search($this->_ldapconn, $this->LDAP_BASEDN, $filter/*, $attributes*/);
			if ($search) {
				$info = ldap_get_entries($this->_ldapconn, $search);
				//debug($info);

				if ($info['count'] == 0) {
					$this->error = "User not found in LDAP {$this->LDAP_BASEDN} [{$filter}]";
					return false;
				}

				for ($i = 0; $i < $info['count']; $i++) {
					//$this->reconnect();
					// Warning: ldap_bind(): Unable to bind to server: Invalid credentials
					$ldapbind = @ldap_bind($this->_ldapconn, $info[$i]['dn'], $password);

					if ($ldapbind) {
						/** @var LDAPUser $user */
						$user = new $this->userClass();
						$user->initLDAP($info[$i]);
						return $user;
					} else {
						$this->error = "LDAP login failed. Probably wrong password";
						//echo getDebug($ldapbind);
						return false;
					}
				}
			} else {
				throw new LoginException(error_get_last());
			}
		}
		return false;
	}

	/**
	 * Substring searches fail on any attribute with a DN syntax
	 * http://support.novell.com/docs/Tids/Solutions/10092520.html
	 * @param $group
	 * @return array
	 */
	public function getUsersFrom($group)
	{
		//$query = '(&(objectCategory=user)(groupMembership=ou=Application_Development))';
		//$query = '(&(objectClass=inetOrgPerson)(groupMembership=cn=Prog_DevApp,ou=Application_Development,ou=FFM2,ou=NOE,o=NWW))';
		//$query = '(&(objectClass=inetOrgPerson)(groupMembership=*cn=Application_Development,ou=FFM2,ou=NOE,o=NWW))';
		$query = '(&(objectClass=inetOrgPerson))';
		//$query = '(cn=*)';
		//$query = '(|(name=memberof)(cn=memberof)(sn=memberof)(displayName=memberof)(givenName=memberof)(uid=memberof)
		//(initials=memberof)(gecos=memberof)(ou=memberof)(dc=memberof)(o=memberof)(group=memberof)(dmdName=memberof)
		//(sAMAccountName=memberof)(description=memberof)(labeledURI=memberof))';
		$this->_connectLdap();

		$search = ldap_search($this->_ldapconn, $group, $query);
		$info = ldap_get_entries($this->_ldapconn, $search);
		unset($info['count']);
		$userClass = get_class($this->userClass);
		foreach ($info as &$user) {
			$user = new $userClass($user);
		}
		return $info;
	}

	/**
	 * @param $query
	 * @return LDAPUser[]
	 */
	public function query($query)
	{
		$this->_connectLdap();

		$search = ldap_search($this->_ldapconn, $this->LDAP_BASEDN, $query, [], null, 50);
		if (!$search) {
			return null;
		}
		$info = ldap_get_entries($this->_ldapconn, $search);
		unset($info['count']);
		$userClass = get_class($this->userClass);
		foreach ($info as &$user) {
			$user = new $userClass($user);
		}
		return $info;
	}
}
