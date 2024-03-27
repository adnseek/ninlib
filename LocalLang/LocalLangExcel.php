<?php

/**
 * Singleton
 *
 */
class LocalLangExcel extends LocalLang
{
	protected $filename = 'lib/LocalLang.object';
	protected $excel = 'lib/translation.xml';
	protected $isCache = true;

	public function __construct($forceLang = null)
	{
		parent::__construct($forceLang);
		$this->ll = $this->readPersistant();
		if (!$this->ll) {
			$this->ll = $this->readExcel(array_merge(['code'], $this->possibleLangs));
			if ($this->ll) {
				$this->savePersistant($this->ll);
			}
		}
		$this->ll = $this->ll[$this->lang];
	}

	public function readPersistant()
	{
		$data = [];
		if (file_exists($this->filename)) {
			if (filemtime($this->filename) > filemtime($this->excel) && $this->isCache) {
				$data = file_get_contents($this->filename);
				$data = unserialize($data);
			}
		}
		return $data;
	}

	public function savePersistant($data)
	{
		$data = serialize($data);
		$data = file_put_contents($this->filename, $data);
		//debug('save');
	}

	public function readExcel(array $keys)
	{
		//debug($keys);
		$data = [];
		if (file_exists($this->excel)) {
			$filedata = file_get_contents($this->excel);
			$filedata = str_replace('xmlns="http://www.w3.org/TR/REC-html40"', '', $filedata);
			$xml = simplexml_load_string($filedata);
			$namespaces = $xml->getNamespaces(true);
			//debug($namespaces);
			foreach ($namespaces as $prefix => $ns) {
				$xml->registerXPathNamespace($prefix, $ns);
			}
			$s = $xml->Worksheet[0]->Table;
			foreach ($s->Row as $row) {
				//debug($row);
				//$dataLine = array();
				//debug(sizeof($row->Cell));
				$i = 0;
				foreach ($row->Cell as $cell) {
					//debug(array($i, $cell));
					$key = $keys[$i++];
					if ($key) {
						//$dataLine[$key] = utf8_decode($cell->Data);
						$cellText = $cell->Data;
						if (!$cellText) {
							//$cellText = $cell->Data->children('http://www.w3.org/TR/REC-html40');
							$cellText = $cell->asXML();
							$cellText = strip_tags($cellText);
							//debug($cellText);
						}
						//$cellText = mb_convert_encoding($cellText, 'Windows-1251', 'UTF-8');
						$cellText = trim($cellText);
						$cellIndex = $cell['ss:Index'] + 0;
						//debug($cell->attributes()->asXML(), $i);
						if (!$cellIndex) {
							$cellIndex = sizeof($data[$key]);
						}
						if ($cellText) {
							$data[$key][$cellIndex] = $cellText;
						}
					}
				}
				//debug($dataLine);
			}
		}
		//debug($data);
		foreach ($data as $lang => &$trans) {
			if ($lang != 'code') {
				//$trans = array_unique($trans);
				//debug(sizeof($trans));
				$trans = array_slice($trans, 0, sizeof($data['code']));
				/*				debug(array(
									'array_combine',
									$data['code'],
									$trans,
								));
				*/
				if (sizeof($data['code']) == sizeof($trans)) {
					$trans = array_combine($data['code'], $trans);
				} else {
					$diff = array_diff_key($data['code'], $trans);
					debug($diff, 'Error in ' . __METHOD__);
				}
			}
		}
		//debug($data);
		return $data;
	}

	public static function getInstance($forceLang = null, $filename = null)
	{
		static $instance = null;
		if (!$instance) {
			$instance = new LocalLangExcel();
		}
		return $instance;
	}

	public function saveMissingMessage($text)
	{
		if (DEVELOPMENT) {
			$missingWords = [];
			$fp = fopen('lib/missing.txt', 'r');
			while (!feof($fp)) {
				$line = fgets($fp);
				$line = trim($line);
				$missingWords[$line] = $line;
			}
			fclose($fp);
			//debug($missingWords);

			if (!isset($missingWords[$text])) {
				$fp = fopen('lib/missing.txt', 'a');
				fputs($fp, $text . "\n");
				fclose($fp);
			}
		}
	}

}
