<?php

/**
 * Class LocalLangTest
 * It's reading data from the class/ll-en.json file and writes new messages back to it
 */
class LocalLangJson extends LocalLangDummy
{

	public $langFolder;

	public function __construct($langFolder = 'class/')
	{
		parent::__construct();
		$this->langFolder = $langFolder;
//		$this->log(__METHOD__, $this->langFolder);
//		ob_start();
//		debug_print_backtrace();
//		$bt = ob_get_clean();
//		$this->log(__METHOD__, $bt);
	}

	public function areThereTranslationsFor($lang)
	{
		$this->lang = $lang;  // temporary
		$file = $this->getFilename();
		$ok = is_file($file);
		//debug($lang, $file, $ok);
		return $ok;
	}

	/**
	 * @return string
	 */
	public function getFilename()
	{
		return $this->langFolder . 'll-' . $this->lang . '.json';
	}

	public function readDB()
	{
//		$this->log(__METHOD__, $this->getFilename());
		$file = file_get_contents($this->getFilename());
		$this->ll = json_decode($file, true);
		//debug($file, $this->ll);
	}

	public function __destruct()
	{
		$jsonEncode = json_encode($this->ll, JSON_PRETTY_PRINT);
		$file = $this->getFilename();
		if (filesize($file) < mb_strlen($jsonEncode)) {
			file_put_contents($file, $jsonEncode);
		}
	}

	public function saveMissingMessage($text)
	{
		$this->updateMessage([
			//'code' => RandomStringGenerator::likeYouTube(),
			'code' => $text,
			'text' => $text,
		]);
	}

	public function updateMessage(array $data)
	{
		$this->ll[$data['code']] = $data['text'];
	}

	public function getEditLinkMaybe($text, $id = null, $class = 'untranslatedMessage')
	{
		if ($this->indicateUntranslated) {
			$trans = new HtmlString('<span class="untranslatedMessage">[' . htmlspecialchars($text) . ']</span>');
		} else {
			$trans = $text;
		}
		return $trans;
	}

}
