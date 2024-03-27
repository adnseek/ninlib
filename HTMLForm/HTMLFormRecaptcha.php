<?php

/**
 * Class HTMLFormRecaptcha
 * http://stackoverflow.com/questions/16890975/using-recaptcha-via-proxy-in-php
 */
class HTMLFormRecaptcha
{

	public $publickey;
	protected $privatekey;

	public function __construct()
	{
		$this->publickey = Config::getInstance()->recaptcha['publickey'];
		$this->privatekey = Config::getInstance()->recaptcha['privatekey'];
		if (!$this->publickey || !$this->privatekey) {
			throw new Exception('Please define publickey and privatekey for Recaptcha.');
		}
		//$error = htmlspecialchars(urlencode($desc['captcha-error'] ? '' : ''), ENT_QUOTES);
		Index::getInstance()->addJS('//www.google.com/recaptcha/api/js/recaptcha_ajax.js'); //?error=' . $error);
	}

	public function getForm(array $desc)
	{
		$r = Request::getInstance();
		$content = recaptcha_get_html($this->publickey, ifsetor($desc['error']), $r->isHTTPS());
		return $content;
	}

	public function getFormAjax(array $desc)
	{
		$content = '
		<div id="recaptcha_div"></div>
 		<script>
 			Recaptcha.create("' . $this->publickey . '", "recaptcha_div");
 		</script>
 		<input type="hidden" name="' . $desc['name'] . '">
 		<!--input type="hidden" name="recaptcha_challenge_field"-->
 		<!--input type="hidden" name="recaptcha_response_field"-->';
		return $content;
	}

	public function validate($field, array $d)
	{
		if (ifsetor($_REQUEST["recaptcha_challenge_field"]) && ifsetor($_REQUEST["recaptcha_response_field"])) {
			//define("RECAPTCHA_VERIFY_SERVER", gethostbyname("www.google.com"));
			$resp = recaptcha_check_answer(
				$this->privatekey,
				$_SERVER["REMOTE_ADDR"],
				$_REQUEST["recaptcha_challenge_field"],
				$_REQUEST["recaptcha_response_field"]
			);
			//debug($resp);
			if (!$resp->is_valid) {
				return __('Recaptcha: ' . $resp->error);
			}
		} else {
			return __('Field "%1" is obligatory.', $d['label'] ?: $field);
		}
		return '';
	}

}
