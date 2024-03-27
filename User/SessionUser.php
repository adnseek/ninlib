<?php

/**
 * SessionUser is stored in the database and has an ID even though the login is stored in the session.
 */
class SessionUser extends PlainSessionUser
{

	public function __construct($id = null)
	{
		parent::__construct($id);
		if (get_class($this) === 'LoginUser') {
			$this->autologin(); // the main difference of SessionUser from PlainSessionUser
		}
	}

	/**
	 * @throws Exception
	 */
	public function autologin()
	{
		$class = static::class;
		$login = $_SESSION[$class]['login'];
		if (ifsetor($_SESSION[$class]) && $login) {
			$inSession = $this->checkPassword($login, $_SESSION[$class]['password']);
			if ($inSession) {
				//$this->findInDB(array('email' => $login));
				$this->init($login);
			} else {
				//throw new Exception('You are not logged in. Nevermind, you can do it later.');
			}
		}
	}

	public function checkPassword($login, $sessionPassword)
	{
		return false;
	}

	public function autoCreate($email)
	{
		// we go here only if not logged in
		// if not a new email and no password we need to ask for password
		$u = new User(); // not to mess-up with current object
		$u->findInDB(['email' => $email]);
		if ($u->id) {
			throw new Exception(__('Your e-mail is known to the system. Please enter a password.<br>
			<a href="?c=ForgotPassword">Forgot password?</a>'));
		} else {
			$password = random_int(1000000, 9999999);
			if (DEVELOPMENT) {
				print 'Generated password: ' . $password;
			}
			$this->insert([
				'email' => $email,
				'password' => $password,
			]);

			$dataObj = new stdClass();
			$dataObj->email = $email;
			$dataObj->password = $password;

			$config = Config::getInstance();
			$body = new View(__DIR__ . '/emailNewAutoAccount.phtml', $dataObj);
			mail($email, 'Account created', $body, "From: " . $config->mailFrom);

			$this->saveLogin($email, md5($password));
			//$this->autologin();
		}
	}

	/**
	 * Session only stores MD5'ed passwords! It can't be otherwise!
	 * This is a success function which loads user data as well.
	 *
	 * @param string $email
	 * @param string $password - hash
	 * @throws Exception
	 */
	public function saveLogin($email = null, $password = null)
	{
		if (strlen($password) != 32) {
			throw new Exception(__METHOD__ . ': supplied password is not hash.');
		}

		if ($this->id) {
			$class = static::class;
			$_SESSION[$class]['login'] = $email;
			$_SESSION[$class]['password'] = $password;
		} else {
			//debug($this->data, 'saveLogin');
			throw new Exception('Login/password matched, but DB retrieval not.');
		}
	}

	public function logout()
	{
		$class = static::class;
		unset($_SESSION[$class]);
		session_regenerate_id(true);
		session_destroy();
	}

}
