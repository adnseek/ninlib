<?php

/**
 * Singleton
 * @phpstan-consistent-constructor
 */
abstract class LocalLang
{
	/**
	 * actual messages
	 * @var array
	 */
	public $ll = [];

	protected $defaultLang = 'en';

	public $possibleLangs = ['en', 'de', 'es', 'ru', 'uk'];

	/**
	 * name of the selected language
	 * @var string
	 */
	public $lang;

	public $indicateUntranslated = false;

	protected $codeID = [];

	public $editMode = false;

	/**
	 * @var bool
	 */
	public $debug = false;

	public $saveMissingMessages = true;

	/**
	 * @var LocalLang|LocalLangJsonPerController
	 */
	public static $instance;

	/**
	 * Will detect the language by the cookie or browser sniffing
	 * @param string $forceLang
	 */
	public function __construct($forceLang = null)
	{
		if (isset($_REQUEST['setLangCookie']) && $_REQUEST['setLangCookie']) {
			$_COOKIE['lang'] = $_REQUEST['setLangCookie'];
			setcookie('lang', $_REQUEST['setLangCookie'], time() + 365 * 24 * 60 * 60, dirname($_SERVER['PHP_SELF']));
		}

		// detect language
		if ($forceLang) {
			$this->lang = $forceLang;
		} else {
			$this->detectLang();
		}

		if (class_exists('Config')) {
			$c = Config::getInstance();
			if (isset($c->config[__CLASS__])) {
				foreach ($c->config[__CLASS__] as $key => $val) {
					$this->$key = $val;
				}
			}
			//debug($c->config, $c->config[__CLASS__], $this);
		}

		// Read language data from somewhere in a subclass
	}

	public function detectLang()
	{
		$l = new LanguageDetect();
//		debug($this->ll);
//		debug($l->languages);
		$replace = false;
		foreach ($l->languages as $lang) {
			if ($this->areThereTranslationsFor($lang)) {
				//debug($lang.' - '.sizeof($this->ll));
				$this->lang = $lang;
				$replace = true;
				break;
			}
		}
		if (!$replace) {
			/*			$firstKey = array_keys($this->ll);
						reset($firstKey);
						$firstKey = current($firstKey);
						$this->ll = $this->ll[$firstKey];
			*/
			$this->lang = $this->defaultLang;
			//debug('firstKey: '.$firstKey);
		}
		//debug($this->ll);

		// allow to force a language by cookie
		$this->lang = isset($_COOKIE['lang']) && $_COOKIE['lang'] && in_array($_COOKIE['lang'], $this->possibleLangs)
			? $_COOKIE['lang']
			: $this->lang;
	}

	public function areThereTranslationsFor($lang)
	{
		return isset($this->ll[$lang]);
	}

	public static function getInstance($forceLang = null, $filename = null)
	{
		if (!self::$instance) {
			self::$instance = new static($forceLang, $filename);
		}
		return self::$instance;
	}

	/**
	 *
	 * @param $text
	 * @param string|array $replace can be a simple %1 replacement, but can also
	 * be an array of alternative translations
	 * @param mixed $s2
	 * @param mixed $s3
	 * @return string translated message
	 * @internal param $ <type> $replace
	 * @internal param $ <type> $s2
	 * @internal param $ <type> $text
	 */
	public function T($text, $replace = null, $s2 = null, $s3 = null)
	{
		if (!is_scalar($text)) {
			throw new InvalidArgumentException('[' . $text . ']');
		}
		if (is_array($replace)) {
			$trans = ifsetor($replace[$this->lang]);
			$trans = $this->Tp($trans, $s2, $s3);
		} else {
			if (isset($this->ll[$text])) {
				$trans = ifsetor($this->ll[$text], $text);
				$trans = $this->Tp($trans, $replace, $s2, $s3);
				$trans = $this->getEditLinkMaybe($trans, $text, '');
				//if ($text == 'Search') { debug($text, $trans); }
			} else {
				//debug($this->ll);
				//debug($text, $this->ll[$text], spl_object_hash($this));
				$this->saveMissingMessage($text);
				$trans = $this->Tp($text, $replace, $s2, $s3);
				$trans = $this->getEditLinkMaybe($trans);
			}
		}
		if ($this->debug = $text == 'asd') {
			//debug($text, isset($this->ll[$text]), $this->ll[$text], $trans);
		}
		return $trans;
	}

	/**
	 * Bare plain-text localization without outputting any HTML
	 * @param $trans
	 * @param mixed $replace
	 * @param mixed $s2
	 * @param mixed $s3
	 * @return mixed|null
	 */
	public static function Tp($trans, $replace = null, $s2 = null, $s3 = null)
	{
		if (is_array($replace)) {
			foreach ($replace as $key => $val) {
				$trans = str_replace('{' . $key . '}', $val, $trans);
			}
		} else {
			$trans = str_replace('%s', $replace, $trans);
			$trans = str_replace('%1', $replace, $trans);
			$trans = str_replace('%2', $s2, $trans);
			$trans = str_replace('%3', $s3, $trans);
		}
		return $trans;
	}

	public function getEditLinkMaybe($text, $id = null, $class = 'untranslatedMessage')
	{
		if ($this->editMode && $id) {
			$trans = '<span class="' . $class . ' clickTranslate" rel="' . htmlspecialchars($id) . '">' . $text . '</span>';
			$al = AutoLoad::getInstance();
			$index = Index::getInstance();
			$index->addJQuery();
			$index->addJS($al->nadlibFromDocRoot . 'js/clickTranslate.js');
			$index->addCSS($al->nadlibFromDocRoot . 'CSS/clickTranslate.css');
		} elseif ($this->indicateUntranslated) {
			$trans = '<span class="untranslatedMessage">[' . $text . ']</span>';
		} else {
			$trans = $text;
		}
		return $trans;
	}

	abstract public function saveMissingMessage($text);

	public function M($text)
	{
		return $this->T($text);
	}

	public function getMessages()
	{
		return $this->ll;
	}

	public function id($code)
	{
		return ifsetor($this->codeID[$code]);
	}

	/**
	 * This doesn't work in Chrome somehow
	 * @return string
	 * @throws Exception
	 */
	public function showLangSelectionDropDown()
	{
		$options = '';
		foreach ($this->possibleLangs as $code) {
			$selected = $this->lang == $code ? ' selected="selected"' : '';
			$options .= '<option value="' . $code . '"' . $selected . '>' . __($code) . '</option>';
		}
		$content = '
		<form action="' . $_SERVER['REQUEST_URI'] . '" method="POST">
			<select class="input-small langMenu" name="setLangCookie">' . $options . '
			</select>
		</form>';
		Index::getInstance()->addCSS('vendor/jquery-switch-master/jquery.switch/jquery.switch.css');
		Index::getInstance()->addJS('vendor/jquery-switch-master/jquery.switch/jquery.switch.min.js');
		//Index::getInstance()->addJS('vendor/jquery-switch-master/jquery.switch/jquery.switch.js');
		return $content;
	}

	public function log($method, $data)
	{
//		error_log('['.$method.'] '. (is_scalar($data) ? $data : json_encode($data)));
	}

}
