<?php

/**
 * Class LocalLangTest
 * It's reading data from the file corresponding to the controller
 */
class LocalLangJsonPerController extends LocalLangJson
{

	/**
	 * @var string
	 */
	public $controller;

	/**
	 * @var LocalLangJson
	 */
	public $general;

	public function __construct($langFolder, $controller, LocalLang $general = null)
	{
		parent::__construct($langFolder);
		$this->controller = $controller;
		$this->general = $general ?: new LocalLangJson($this->langFolder);
		//debug($this->lang);
		if ($this->controller) {
			$this->detectLang();
			$this->readDB();
		}
		$this->log(__METHOD__, $this->controller);
		LocalLang::$instance = $this;
	}

	public function setController($class)
	{
		$this->log(__METHOD__, $class);
		//debug(__METHOD__, $class);
		$this->controller = $class;
		$this->detectLang();
		$this->readDB();
	}

	public function readDB()
	{
		$this->log(__METHOD__, $this->getFilename());
		$file = $this->getFilename();
		if (is_file($file)) {
			$file = file_get_contents($file);
			$this->ll = json_decode($file, true);
			$this->log(__METHOD__, sizeof($this->ll));
		}
	}

	public function __destruct()
	{
		$jsonEncode = json_encode($this->ll, JSON_PRETTY_PRINT);
		$file = $this->getFilename();
		if (!is_file($file) || (filesize($file) < mb_strlen($jsonEncode))) {
			file_put_contents($file, $jsonEncode);
		}
	}

	/**
	 * @return string
	 */
	public function getFilename()
	{
		return $this->langFolder . $this->controller . '-' . $this->lang . '.json';
	}

	public function T($text, $replace = null, $s2 = null, $s3 = null)
	{
		if (isset($this->ll[$text])) {
			return parent::T($text, $replace, $s2, $s3);
		} elseif (isset($this->general->ll[$text])) {
			return $this->general->T($text, $replace, $s2, $s3);
		} else {
			$this->saveMissingMessage($text);
			return $this->Tp($text, $replace, $s2, $s3);
		}
	}

}
