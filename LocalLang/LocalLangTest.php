<?php

/**
 * Class LocalLangTest
 * It's reading data from the class/ll-en.json file and writes new messages back to it
 */
class LocalLangTest extends LocalLangDummy
{

	public function __construct()
	{
		parent::__construct();
		$file = file_get_contents('class/ll-' . $this->lang . '.json');
		$this->ll = json_decode($file, true);
		//debug($file, $this->ll);
	}

	public function updateMessage(array $data)
	{
		$this->ll[$data['code']] = $data['text'];
		file_put_contents('class/ll-' . $this->lang . '.json', json_encode($this->ll));
	}

}
