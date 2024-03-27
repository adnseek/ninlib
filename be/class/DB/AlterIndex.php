<?php

use spidgorny\nadlib\HTTP\URL;

class AlterIndex extends AppControllerBE
{

	/**
	 * @var string
	 */
	var $jsonFile;

	/**
	 * @var DBLayerBase
	 */
	var $db;

	public function __construct()
	{
		parent::__construct();
		$host = gethostname() ?: $_SERVER['SERVER_NAME'];
		$filename = $this->request->getFilename('file') ?:
			$host . '-' . $this->db->database . '.json';
		if (!Path::isItAbsolute($filename)) {
			$appRoot = AutoLoad::getInstance()->getAppRoot();
			$this->jsonFile = $appRoot . '/sql/' . $filename;
		}
	}

	public function sidebar()
	{
		$content = [];
		if (class_exists('AdminPage')) {
			$ap = new AdminPage();
			$content[] = $ap->sidebar();
		}

		$content[] = $this->showDBInfo();
		$content[] = $this->listFiles();

		return $content;
	}

	public function showDBInfo()
	{
		$content[] = 'Schema: ' . $this->db->getScheme() . BR;
		$content[] = 'Wrapper: ' . get_class($this->db) . BR;
		$content[] = 'DB: ' . $this->db->database . BR;
		$content[] = 'File: ' . basename($this->jsonFile) . BR;
		if ($this->db->database) {
			$content[] = $this->getActionButton('Save DB Struct', 'saveStruct', null, [], 'btn btn-info');
		}
		return $content;
	}

	public function listFiles()
	{
		$li = [];
		$files = new ListFilesIn(AutoLoad::getInstance()->getAppRoot() . '/sql/');
		foreach ($files as $file) {
			/** @var $file File|Recursive */
			if ($file instanceof File) {
				if ($file->getExtension() == 'json') {
					$li[] = $this->a(new URL(null, [
							'c' => get_class($this),
							'file' => basename($file),
						]), basename($file)) .
						'<div style="float: right;">[' . date('Y-m-d H:i', $file->getCTime()) . ']</div>';
				}
			}
		}
		$ul = new UL($li);
		$content[] = $ul;
		return $content;
	}

	public function saveStructAction()
	{
		$struct = $this->getDBStruct();
		if (phpversion() > '5.4') {
			$json = json_encode($struct, JSON_PRETTY_PRINT);
		} else {
			$json = json_encode($struct);
		}

		file_put_contents($this->jsonFile, $json);
		return 'Saved: ' . strlen($json) . '<br />';
	}

	public function getDBStruct()
	{
		$result = [];
		$tables = $this->db->getTables();
		foreach ($tables as $t) {
			$struct = $this->db->getTableColumnsEx($t);
			$indexes = $this->db->getIndexesFrom($t);
			$result[$t] = [
				'columns' => $struct,
				'indexes' => $indexes,
			];
		}
		return $result;
	}

	public function render()
	{
		$content[] = $this->performAction($this->detectAction());
		if ($this->jsonFile && is_readable($this->jsonFile)) {
			$struct = file_get_contents($this->jsonFile);
			$struct = json_decode($struct, true);
			if (!$struct) {
				throw new Exception('JSON file reading error');
			}

			$local = $this->getDBStruct();

			$content[] = $this->renderTableStruct($struct, $local);
		} else {
			$content[] = '<div class="message">Choose file on the left</div>';
		}
		return $content;
	}

	public function renderTableStruct(array $struct, array $local)
	{
		$content = '';
		foreach ($struct as $table => $desc) {
			$content .= '<h4 id="table-' . $table . '">Table: ' . $table . '</h4>';

			$indexCompare = $this->compareTable($table, $local, $desc);
			$content .= new slTable($indexCompare, 'class="nospacing table table-striped"', [
				'same' => 'Same',
				'Table' => 'Table',
				'Non_unique' => 'Non_unique',
				'Key_name' => 'Key_name',
				'Seq_in_index' => 'Seq_in_index',
				'Column_name' => 'Column_name',
				'Collation' => 'Collation',
				'Cardinality' => 'Cardinality',
				//'Sub_part' => 'Sub_part',
				//'Packed' => 'Packed',
				'Null' => 'Null',
				'Index_type' => 'Index_type',
				'Comment' => 'Comment',
				//'Index_comment' => 'Index_comment',
			]);
		}
		return $content;
	}

	/**
	 * @param string $table
	 * @param array $local
	 * @param array $desc
	 * @return array
	 */
	protected function compareTable($table, array $local, array $desc)
	{
		$indexCompare = [];
		foreach ($desc['indexes'] as $i => $index) {
			$index = $this->convertFromOtherDB($index);
			$localIndex = $local[$table]['indexes'][$i];
			$localIndex = $this->convertFromOtherDB($localIndex);
			//debug($index, $localIndex);

			unset($index['Cardinality'], $localIndex['Cardinality']);    // changes over time
			if ($index != $localIndex) {
				//$content .= getDebug($index, $localIndex);
				if (is_array($index)) {
					$indexCompare[] = [
							'same' => 'sql file',
							'###TR_MORE###' => 'style="background: pink"',
						] + $index;
				}
				if (is_array($localIndex)) {
					$indexCompare[] = [
							'same' => 'database',
							'###TR_MORE###' => 'style="background: pink"',
						] + $localIndex;
				} else {
					$indexCompare[] = [
						'Table' => new HTMLTag('td', [
							'colspan' => 10,
						], 'CREATE ' . ($index['Non_unique'] ? '' : 'UNIQUE') .
							' INDEX ' . $index['Key_name'] .
							' ON ' . $index['Table'] . ' (' . $index['Key_name'] . ')'
						),
					];
				}
			} else {
				//$content .= 'Same index: '.$index['Key_name'].' '.$localIndex['Key_name'].'<br />';
				$index['same'] = 'same';
				$index['###TR_MORE###'] = 'style="background: lightyellow"';
				$indexCompare[] = $index;
				//debug($index); exit();
			}
		}
		return $indexCompare;
	}

	public function convertFromOtherDB(array $desc)
	{
		if ($desc['tbl_name']) {    // SQLite
			$desc['Table'] = $desc['tbl_name'];
			unset($desc['tbl_name']);
			$desc['Key_name'] = $desc['name'];
			unset($desc['name']);
			$desc['Index_type'] = $desc['type'];
			unset($desc['type']);
			unset($desc['rootpage']);
			$desc['comment'] = $desc['sql'];
			unset($desc['sql']);
		}
		return $desc;
	}

}
