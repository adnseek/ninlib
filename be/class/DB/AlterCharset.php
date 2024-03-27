<?php

use spidgorny\nadlib\HTTP\URL;

/**
 * Created by JetBrains PhpStorm.
 * User: VirtualSlawa
 * Date: 4/16/13
 * Time: 11:24 PM
 * To change this template use File | Settings | File Templates.
 */
class AlterCharset extends AppControllerBE
{

	public $desired = 'utf8_general_ci';

	public function render()
	{
		$this->index->addJS(AutoLoad::getInstance()->nadlibFromDocRoot . '/js/keepScrollPosition.js');
		$content = $this->performAction($this->detectAction());
		if (!is_object($this->db)) {
			debug($this->db);
			return 'No db object';
		}
		$tables = $this->db->getTables();
		foreach ($tables as $table) {
			$charset = current($this->db->getTableCharset($table));
			$content .= '<h4 style="display: inline-block; width: 30em;">' . $table . '</h4>';
			$charsetColor = $charset == $this->desired ? 'muted' : 'alert';
			$content .= ' <span class="' . $charsetColor . '">' . $charset . '</span>';
			if ($charset != $this->desired) {
				$content .= ' <a href="' . new URL('', [
						'c' => __CLASS__,
						'action' => 'alterTableCharset',
						'table' => $table,
					]) . '">ALTER</a>';
			}
			$content .= $this->renderTableColumns($table);

			$content .= '<br />';
		}
		return $content;
	}

	public function renderTableColumns($table)
	{
		$badList = [];
		$columns = $this->db->getTableColumns($table);
		foreach ($columns as $row) {
			if ($row['Collation'] && $row['Collation'] != $this->desired) {
				$row['alter'] = ' <a href="' . new URL('', [
						'c' => __CLASS__,
						'action' => 'alterColumnCharset',
						'table' => $table,
						'column' => $row['Field'],
					]) . '">ALTER</a>';
				$row['alter'] = ' <a href="http://localhost/adminer/?server=127.0.0.1&username=root&db=t3vpc&create=' . $table . '">Adminer</a>';
				$badList[] = $row;
			}
		}
		$s = new slTable($badList, '', [
			'Field' =>
				[
					'name' => 'Field',
				],
			'Type' =>
				[
					'name' => 'Type',
				],
			'Collation' =>
				[
					'name' => 'Collation',
				],
			'Null' =>
				[
					'name' => 'Null',
				],
			'Key' =>
				[
					'name' => 'Key',
				],
			'Default' =>
				[
					'name' => 'Default',
				],
			'Extra' =>
				[
					'name' => 'Extra',
				],
			'Privileges' =>
				[
					'name' => 'Privileges',
				],
			'Comment' =>
				[
					'name' => 'Comment',
				],
			'alter' =>
				[
					'name' => 'alter',
					'no_hsc' => true,
				],
		]);
		//$s->generateThes();
		//var_export($s->thes);
		return $s;
	}

	public function alterTableCharsetAction()
	{
		$table = $this->request->getTrim('table');
		$query = "ALTER TABLE " . $table . " DEFAULT COLLATE = '" . $this->desired . "'";
		$this->db->perform($query);
	}

	/**
	 * Possibly dangerous if we don't recreate the complete column definition as it was
	 */
	public function alterColumnCharsetAction()
	{
		$table = $this->request->getTrim('table');
		$column = $this->request->getTrim('column');
		$query = "ALTER TABLE " . $table . " CHANGE `identifier` `identifier` varchar(250) COLLATE '" . $this->desired . "' NOT NULL DEFAULT ''";
		$this->db->perform($query);
	}

}
