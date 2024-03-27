<?php
/**
 * Created by PhpStorm.
 * User: Slawa
 * Date: 2018-11-28
 * Time: 00:17
 * This is like slTable but without the legacy code
 */

class HTMLTable
{

	public $data = [];

	public $thes = [];

	/**
	 * HTMLTable constructor.
	 *
	 * @param array|object|null $res
	 * @param array $thes
	 */
	public function __construct(array $res, array $thes = [])
	{
		$this->data = $res;
		$this->thes = $thes;
	}

	public function __toString()
	{
		if (!$this->thes) {
			$this->genThes();
		}
		return implode(PHP_EOL, $this->table());
	}

	public function genThes()
	{
		$col1 = current($this->data);
		$this->thes = $col1;
	}

	public function table()
	{
		$content['table'] = '<table>';
		$content['thead'] = '<thead><tr>';
		foreach ((array)$this->thes as $key => $_) {
			$content['th.' . $key] = '<th>' . htmlspecialchars($key) . '</th>';
		}
		$content['/thead'] = '</tr></thead>';
		$content['tbody'] = '<tbody>';
		foreach ($this->data as $row) {
			$content[] = $this->row((array)$row);
		}
		$content['/tbody'] = '<tbody>';
		$content['/table'] = '</table>';
		return $content;
	}

	public function row(array $row)
	{
		$content[] = '<tr>';
		foreach ($this->thes as $key => $_) {
			$cell = ifsetor($row[$key]);
			if ($cell instanceof HTMLTag) {
			} elseif ($cell instanceof HtmlString) {
			} else {
				$cell = htmlspecialchars($cell ?? '');
			}
			$content[] = '<td>' . $cell . '</td>';
		}
		$content[] = '</tr>';

		return implode(PHP_EOL, $content);
	}

}
