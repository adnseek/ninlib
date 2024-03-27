<?php

require_once __DIR__ . '/functions.php';

/**
 * Class RandomStringGenerator
 * @package Utils
 *
 * Solution taken from here:
 * http://stackoverflow.com/a/13733588/1056679
 */
class RandomStringGenerator
{
	/** @var string */
	protected $alphabet;

	/** @var int */
	protected $alphabetLength;


	/**
	 * @param string $alphabet
	 */
	public function __construct($alphabet = '')
	{
		if ('' !== $alphabet) {
			$this->setAlphabet($alphabet);
		} else {
			$this->setAlphabet(
				implode(range('a', 'z'))
				. implode(range('A', 'Z'))
				. implode(range(0, 9))
			);
		}
	}

	/**
	 * @param string $alphabet
	 */
	public function setAlphabet($alphabet)
	{
		$this->alphabet = $alphabet;
		$this->alphabetLength = strlen($alphabet);
	}

	/**
	 * @param int $length
	 * @return string
	 */
	public function generate($length)
	{
		$token = '';

		for ($i = 0; $i < $length; $i++) {
			$randomKey = $this->getRandomInteger(0, $this->alphabetLength);
			$token .= $this->alphabet[$randomKey];
		}

		return $token;
	}

	/**
	 * @param int $min
	 * @param int $max
	 * @return int
	 */
	protected function getRandomInteger($min, $max)
	{
		$range = ($max - $min);

		if ($range < 0) {
			// Not so random...
			return $min;
		}

		$log = log($range, 2);

		// Length in bytes.
		$bytes = (int)($log / 8) + 1;

		// Length in bits.
		$bits = (int)$log + 1;

		// Set all lower bits to 1.
		$filter = (int)(1 << $bits) - 1;

		do {
			$rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));

			// Discard irrelevant bits.
			$rnd = $rnd & $filter;

		} while ($rnd >= $range);

		return ($min + $rnd);
	}

	public function generateSplit4($length)
	{
		$continuous = $this->generate($length);
		$parts = chunk_split($continuous, 4, '-');
		if ($parts[strlen($parts) - 1] === '-') {
			$parts = substr($parts, 0, strlen($parts) - 1);
		}
		return $parts;
	}

	public static function likeYouTube(): string
	{
		$gen = new self();
		return $gen->generate(10);
	}

	/**
	 * http://www.anyexample.com/programming/php/php__password_generation.xml
	 * @param int $syllables
	 * @return string
	 */
	public function generateReadablePassword($syllables = 3)
	{
		$use_prefix = false;
		// Define function unless it is already exists
		// 20 prefixes
		$prefix = ['aero', 'anti', 'auto', 'bi', 'bio',
			'cine', 'deca', 'demo', 'dyna', 'eco',
			'ergo', 'geo', 'gyno', 'hypo', 'kilo',
			'mega', 'tera', 'mini', 'nano', 'duo'];

		// 10 random suffixes
		$suffix = ['dom', 'ity', 'ment', 'sion', 'ness',
			'ence', 'er', 'ist', 'tion', 'or'];

		// 8 vowel sounds
		$vowels = ['a', 'o', 'e', 'i', 'y', 'u', 'ou', 'oo'];

		// 20 random consonants
		$consonants = ['w', 'r', 't', 'p', 's', 'd', 'f', 'g', 'h', 'j',
			'k', 'l', 'z', 'x', 'c', 'v', 'b', 'n', 'm', 'qu'];

		$password = $use_prefix ? ae_arr($prefix) : '';
		$password_suffix = ae_arr($suffix);

		for ($i = 0; $i < $syllables; $i++) {
			// selecting random consonant
			$doubles = ['n', 'm', 't', 's'];
			$c = ae_arr($consonants);
			if (in_array($c, $doubles) && ($i != 0)) { // maybe double it
				if (rand(0, 2) == 1) // 33% probability
					$c .= $c;
			}
			$password .= $c;
			//

			// selecting random vowel
			$password .= ae_arr($vowels);

			if ($i == $syllables - 1) // if suffix begin with vovel
				if (in_array($password_suffix[0], $vowels)) // add one more consonant
					$password .= ae_arr($consonants);

		}

		// selecting random suffix
		$password .= $password_suffix;

		return $password;
	}

}
