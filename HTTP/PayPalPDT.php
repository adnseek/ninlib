<?php

class PayPalPDT
{
	// read the post from PayPal system and add 'cmd'
	protected $req = 'cmd=_notify-synch';

	// real
	protected $auth_token = "BqL-aA4wiboHHmyImOagKm9kXwMslvZnahgs2CpqMcuoINc1b8c6A4YV4Te";
	protected $sandbox = '';

	// sandbox
	//protected $auth_token	= "HbWDzBZjWhSpPhA_uLspqCZoENpH26CDVnhVY09LDE8NTOJNG4pIE7dK6f4";
	//protected $sandbox = 'sandbox.';

	public $response = [];      // payment data will appear here

	public function __construct()
	{
		$tx_token = $_GET['tx'];
		$this->req .= "&tx=" . $tx_token . "&at=" . $this->auth_token;
	}

	public function validate()
	{
		$url = 'www.' . $this->sandbox . 'paypal.com';
		// post back to PayPal system to validate
		$header = "POST /cgi-bin/webscr HTTP/1.0\r\n";
		$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$header .= "Content-Length: " . strlen($this->req) . "\r\n\r\n";
		$fp = fsockopen($url, 80, $errno, $errstr, 30);
		// If possible, securely post back to paypal using HTTPS
		// Your PHP server will need to be SSL enabled
		// $fp = fsockopen ('ssl://'.$url, 443, $errno, $errstr, 30);

		if (!$fp) {
			throw new Exception('HTTP ERROR');
		} else {
			fputs($fp, $header . $this->req);
			// read the body data
			$res = '';
			$headerdone = false;
			while (!feof($fp)) {
				$line = fgets($fp, 1024);
				if (strcmp($line, "\r\n") == 0) {
					// read the header
					$headerdone = true;
				} elseif ($headerdone) {
					// header has been read. now read the contents
					$res .= $line;
				}
			}

			// parse the data
			$lines = explode("\n", $res);
			$keyarray = [];
			if (strcmp($lines[0], "SUCCESS") == 0) {
				for ($i = 1; $i < count($lines); $i++) {
					list($key, $val) = explode("=", $lines[$i]);
					$keyarray[urldecode($key)] = urldecode($val);
				}
				//d($url, $header, $this->req, /*$res,*/ $keyarray);
				$this->response = $keyarray;
				return true;
			} elseif (strcmp($lines[0], "FAIL") == 0) {
				throw new Exception($res);
				//d($url, $header, $this->req, $res);
			}
		}

		fclose($fp);
		return false;
	}

}
