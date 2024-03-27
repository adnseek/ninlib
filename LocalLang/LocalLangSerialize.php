<?php

class LocalLangSerialize extends LocalLang
{

	public function __construct($forceLang = null, $filename = null)
	{
		parent::__construct($forceLang);
		$data = file_get_contents($filename);
		$this->ll = unserialize($data);
	}

	public function saveMissingMessage($text)
	{
		// TODO: Implement saveMissingMessage() method.
	}

}
