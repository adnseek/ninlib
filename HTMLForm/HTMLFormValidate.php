<?php

class HTMLFormValidate
{

	/**
	 * Reference to the form object which contains the $desc as well as other vars
	 * @var HTMLFormTable
	 */
	protected $form;

	/**
	 * Reference to the $desc in the form
	 * @var array
	 */
	protected $desc;

	public function __construct(HTMLFormTable $form)
	{
		$this->form = $form;
		$this->desc = &$this->form->desc;
	}

	public function validate()
	{
		$error = false;
		foreach ($this->desc as $field => &$d) {
			if ($d instanceof HTMLFormTable) {
				$v2 = new HTMLFormValidate($d);
				$validateResult = $v2->validate();
				$error = $error || !$validateResult;
				//$d->desc = $v2->getDesc();
				//debug('updateDesc', $d->getFieldset());
			} elseif (ifsetor($d['type']) instanceof HTMLFormType) {
				$d['type']->setValue(ifsetor($d['value']));
				$error = $d['type']->validate();
				if ($error) {
					$d['error'] = $error;
				}
			} else {
				if (ifsetor($d['mustBint'])) {
					$d['value'] = intval($d['value']);
				}
				$type = ifsetor($d['type']);
				if (is_object($type)) {
					$type = get_class($type);
				}
				$isCheckbox = !is_object($type) && in_array($type, [
						'check',
						'checkbox',
						'captcha',
						'recaptcha',
						'recaptchaAjax',
						'select',
					]);
				$dWithError = $this->validateField($field, $d, $type, $isCheckbox);
				$d['error'] = ifsetor($dWithError['error']);
				//debug($field, $d['error']);
				$error = $error || ifsetor($d['error']);
			}
		}
		return !$error;
	}

	public function validateField($field, array $d, $type, $isCheckbox)
	{
		$value = ifsetor($d['value']);
		$label = ifsetor($d['label'], $field);
		$isHidden = in_array($type, ['hidden', 'html']);
		if (!ifsetor($d['optional']) && (
				!($value) || (!ifsetor($d['allow0']) && !isset($d['value'])))
			&& !$isCheckbox && !$isHidden) {
			$d['error'] = __('Field "%1" is obligatory.', $label);
			//debug(array($field, $type, $value, $isCheckbox));
		} elseif ($type instanceof Collection) {
			// all OK, avoid calling __toString on the collection
		} elseif (ifsetor($d['mustBset']) && !isset($d['value'])) {    // must be before 'obligatory'
			$d['error'] = __('Field "%1" must be set', $label);
		} elseif (ifsetor($d['obligatory']) && !$value) {
			$d['error'] = __('Field "%1" is obligatory', $label);
		} elseif (($type == 'email' || $field == 'email') && $value && !self::validEmail($value)) {
			$d['error'] = __('Not a valid e-mail in field "%1"', $label);
		} elseif ($field === 'password' && strlen($value) < ifsetor($d['minlen'], 6)) {
			$d['error'] = __('Password is too short. Min %s characters, please. It\'s for your own safety', ifsetor($d['minlen'], 6));
		} elseif ($field === 'securePassword' && !$this->securePassword($value)) {
			$d['error'] = 'Password must contain at least 8 Characters. One number and one upper case letter. It\'s for your own safety';
		} elseif (ifsetor($d['min']) && ($value < $d['min'])) {
			//debug(__METHOD__, $value, $d['min']);
			$d['error'] = __('Value in field "%1" is too small. Minimum: %2', $label, $d['min']);
		} elseif (ifsetor($d['max']) && ($value > $d['max'])) {
			$d['error'] = __('Value in field "%1" is too large. Maximum: %2', $label, $d['max']);
		} elseif (ifsetor($d['minlen']) && strlen($value) < $d['minlen']) {
			$d['error'] = __('Value in field "%1" is too short. Minimum: %2. Actual: %3', $label, $d['minlen'], strlen($value));
		} elseif (ifsetor($d['maxlen']) && strlen($value) > $d['maxlen']) {
			$d['error'] = __('Value in field "%1" is too long. Maximum: %2. Actual: %3', $label, $d['maxlen'], strlen($value));
		} elseif ($type == 'recaptcha' || $type == 'recaptchaAjax') {
			$hfr = new HTMLFormRecaptcha();
			$d['error'] = $hfr->validate($field, $d);
		} elseif ($value && ifsetor($d['validate']) == 'in_array' && !in_array($value, $d['validateArray'])) {
			$d['error'] = $d['validateError'];
		} elseif ($value && ifsetor($d['validate']) == 'id_in_array' && !in_array($d['idValue'], $d['validateArray'])) { // something typed
			$d['error'] = $d['validateError'];
		} elseif (ifsetor($d['validate']) == 'int' && strval(intval($value)) != $value) {
			$d['error'] = __('Value "%1" must be integer', $label);
		} elseif (ifsetor($d['validate']) == 'date' && strtotime($value) === false) {
			$d['error'] = __('Value "%1" must be date', $label);
		} elseif (ifsetor($d['validate']) == 'multiEmail' && !self::validateEmailAddresses($value, $inValid)) {
			$d['error'] = __('Value "%1" contains following invalid email addresses: "%2"', $label, implode(', ', $inValid));
		} elseif (ifsetor($d['mustMatch'])
			&& $value != $d['mustMatch']) {
			//debug($value, $d['mustMatch']);
			$d['error'] = __('Value does not match');
		} elseif (ifsetor($d['required']) && !$value) {
			$d['error'] = __('Field is required');
		} else {
			unset($d['error']);
			//debug($field, $value, strval(intval($value)), $value == strval(intval($value)));
			if ($field === 'xsrf') {
				//debug($value, $_SESSION['HTMLFormTable']['xsrf'][$this->form->class]);
				if ($value != $_SESSION['HTMLFormTable']['xsrf'][$this->form->class]) {
					$d['error'] = __('XSRF token validation failed.');
				}
			}
		}

		if (ifsetor($d['dependant']) && $isCheckbox && $value) { // only checked should be validated
			//t3lib_div::debug(array($field, $value, $isCheckbox));
			$f2 = new HTMLFormTable($d['dependant']);
			$fv = new HTMLFormValidate($f2);
			if (!$fv->validate()) {
				$d['dependant'] = $fv->getDesc();
				$d['error'] = implode("<br />\n", $fv->getErrorList());
			}
		}
		return $d;
	}

	public function securePassword($value)
	{
		/*
		* REGEX used for password strength check
		*  (?=.*\\d.*)      : at least one Digit
		*  (?=.*[a-zA-Z].*) : any Letters
		*  (?=.*[A-Z])      : at least one Uppercase
		*  {8,}             : 8 Length
		*/
		$passwordRegex = '/(?=.*\\d.*)(?=.*[a-zA-Z].*)(?=.*[A-Z]).{8,}/';
		return (preg_match($passwordRegex, $value));
	}

	public function getDesc()
	{
		return $this->desc;
	}

	//static function validMail($email) {
	//return preg_match("/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\\.[A-Z]{2,4}\\b/i", $email);
	//	return $this->validEmail()
	//}

	/**
	 * Validate an email address.
	 * Provide email address (raw input)
	 * Returns true if the email address has the email
	 * address format and the domain exists.
	 * http://www.linuxjournal.com/article/9585?page=0,3
	 */
	public static function validEmail($email)
	{
		$isValid = true;
		$atIndex = strrpos($email, "@");
		if (is_bool($atIndex) && !$atIndex) {
			$isValid = false;
		} else {
			$domain = substr($email, $atIndex + 1);
			$local = substr($email, 0, $atIndex);
			$localLen = strlen($local);
			$domainLen = strlen($domain);
			if ($localLen < 1 || $localLen > 64) {
				// local part length exceeded
				$isValid = false;
			} elseif ($domainLen < 1 || $domainLen > 255) {
				// domain part length exceeded
				$isValid = false;
			} elseif ($local[0] == '.' || $local[$localLen - 1] == '.') {
				// local part starts or ends with '.'
				$isValid = false;
			} elseif (preg_match('/\\.\\./', $local)) {
				// local part has two consecutive dots
				$isValid = false;
			} elseif (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
				// character not valid in domain part
				$isValid = false;
			} elseif (preg_match('/\\.\\./', $domain)) {
				// domain part has two consecutive dots
				$isValid = false;
			} elseif
			(!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
					str_replace("\\\\", "", $local))) {
				// character not valid in local part unless
				// local part is quoted
				if (!preg_match('/^"(\\\\"|[^"])+"$/',
					str_replace("\\\\", "", $local))) {
					$isValid = false;
				}
			}
			if ($isValid && !(checkdnsrr($domain, "MX") || checkdnsrr($domain, "A"))) {
				// domain not found in DNS
				$isValid = false;
			}
		}
		return $isValid;
	}

	public function getErrorList()
	{
		$list = [];
		foreach ($this->desc as $key => $desc) {
			if (ifsetor($desc['error'])) {
				$list[$key] = $desc['error'];
			}
		}
		return $list;
	}

	/**
	 * If Swift_Mail is installed, Swift_Validate will be used
	 *
	 * @param mixed $value should contain multiple email addresses (comma separated)
	 * @param array $invalid contains invalid entries (pass by reference)
	 * @return bool
	 */
	public static function validateEmailAddresses($value, &$invalid = [])
	{
		$value = trim($value);
		if (empty($value)) {
			return true;
		}

		$emailAddresses = preg_split('/\s*,\s*/', $value);
		foreach ($emailAddresses as &$emailAddress) {
			if ((class_exists('Swift_Validate') && !Swift_Validate::email($emailAddress)) ||
				!self::validEmail($emailAddress)) {
				$invalid[] = $emailAddress;
			}
		}
		return empty($invalid);
	}
}
