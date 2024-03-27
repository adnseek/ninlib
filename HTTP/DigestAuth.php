<?php

class DigestAuth
{
	protected $realm;
	public $userAssoc = [];
	public $username;

	public function __construct($realm)
	{
		$this->realm = $realm;
	}

	// maryna.sigayeva@web.de
	public function run()
	{
		$digestString = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] : $_SERVER['HTTP_AUTORIZATION'];
		$digestString = $digestString ? $digestString : $_SERVER['PHP_AUTH_DIGEST'];
		if (empty($digestString)) {
			$this->headers();
			//debug($digestString);
			die('Auth canceled');
		}

		// analyze the PHP_AUTH_DIGEST variable
		if (!($data = $this->http_digest_parse($digestString)) ||
			!isset($this->userAssoc[$data['username']])) {
			$this->headers();
			//debug($data, $this->userAssoc);
			die('Wrong Credentials!');
		}

		// generate the valid response
		$password = $this->userAssoc[$data['username']];
		//debug($this->realm, $data['username'], $password); exit();
		$A1 = md5($data['username'] . ':' . $this->realm . ':' . $password);
		$A2 = md5($_SERVER['REQUEST_METHOD'] . ':' . $data['uri']);
		$valid_response = md5($A1 . ':' . $data['nonce'] . ':' . $data['nc'] . ':' . $data['cnonce'] . ':' . $data['qop'] . ':' . $A2);

		if ($data['response'] != $valid_response) {
			$this->headers();
			die('Wrong Credentials! (2)');
		}

		$this->username = $data['username'];

		// ok, valid username & password
		//echo 'You are logged in as: ' . $this->username;
		return true;
	}

	public function headers()
	{
		header('HTTP/1.1 401 Unauthorized');
		header('WWW-Authenticate: Digest realm="' . $this->realm .
			'",qop="auth",nonce="' . uniqid() . '",opaque="' . md5($this->realm) . '"');
	}

	// function to parse the http auth header
	public function http_digest_parse($txt)
	{
		// protect against missing data
		$needed_parts = [
			'nonce' => 1,
			'nc' => 1,
			'cnonce' => 1,
			'qop' => 1,
			'username' => 1,
			'uri' => 1,
			'response' => 1];
		$data = [];
		$keys = implode('|', array_keys($needed_parts));

		preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $txt, $matches, PREG_SET_ORDER);

		foreach ($matches as $m) {
			$data[$m[1]] = $m[3] ? $m[3] : $m[4];
			unset($needed_parts[$m[1]]);
		}

		return $needed_parts ? false : $data;
	}

	/**
	 * Reverse function below. Requesting DigestAuth...
	 */

	public function POST($url, $auth, $content)
	{
		$length = strlen($content);

		$headers[] = "Content-Length: $length";

		$poster = curl_init($url);

		curl_setopt($poster, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($poster, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($poster, CURLOPT_TIMEOUT, 60);
		curl_setopt($poster, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($poster, CURLOPT_HEADER, 1);
		curl_setopt($poster, CURLOPT_USERPWD, $auth);
		curl_setopt($poster, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
		curl_setopt($poster, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($poster, CURLOPT_POST, 1);
		curl_setopt($poster, CURLOPT_POSTFIELDS, $content);
		curl_setopt($poster, CURLOPT_VERBOSE, false);
		curl_setopt($poster, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($poster, CURLOPT_HEADER, 0);
		//curl_setopt($poster, CURLOPT_COOKIE, 'debug=1');

		$response = curl_exec($poster);
		$info = curl_getinfo($poster);
		$info['response'] = $response;
		curl_close($poster);
		return $info;
	}

}
