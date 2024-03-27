<?php

/**
 * Specific app implementation of the login form.
 * This one renders nothing because the login form is embedded into the template.
 */
class LoginForm extends AjaxLogin
{

	protected $secret = 'nadlibSecretPasswordHash';

	/**
	 * @var BEUser
	 */
	public $user;

	public function __construct($action = null)
	{
		parent::__construct($action);
		$env = $_ENV;
		unset($env['REDIRECT_UNIQUE_ID']);
		unset($env['UNIQUE_ID']);
		unset($env['DBENTRY']);
		unset($env['HTTP_COOKIE']);
		unset($env['REMOTE_PORT']);
		unset($env['CONTENT_LENGTH']);
		//debug($env);
		$this->secret = md5(json_encode($env));
		$this->layout = new Wrap('<div class="col-md-10">', '</div>' . "\n");
	}

	public function render()
	{
		return '';
	}

	public function loginAction()
	{
		//debug($this->request);
		$content = '';
		$username = $this->request->getTrim('username');
		$password = $this->request->getTrim('password');
		$passwordHash = $this->secret;
		if ($username == 'nadlib' && $password == $passwordHash) {
			$this->user->saveLogin($username, $passwordHash);
			$content .= '<div class="message">' . __('You are logged in.') . '</div>';
			$content .= $this->menuAction();
		} else {
			$content .= getDebug($password, $passwordHash);
			$content .= '<div class="error">' . __('Wrong login or password.') . '</div>';
			$desc = $this->getLoginDesc();
			$desc['username']['value'] = $username;
			$desc['password']['cursor'] = true;
			$content .= $this->formAction($desc);
		}
		return $content;
	}

}
