<?php

class CLITable
{

	public $data = [];

	public $thes = [];

	public $footer = [];

	public function __construct(array $data, array $thes = [])
	{
		$this->data = $data;
		$this->thes = $thes;
		if (!$this->thes) {
			foreach ($this->data as $row) {
				foreach ($row as $key => $val) {
					$this->thes[$key] = $key;
				}
			}
		}
	}

	public function render($cutTooLong = false, $useAvg = false)
	{
		$widthMax = [];
		$widthAvg = [];
		// thes should fit into a columns as well
		foreach ($this->thes as $field => $name) {
			$widthMax[$field] = is_array($name)
				? mb_strlen(ifsetor($name['name']))
				: (mb_strlen($name) ?: mb_strlen($field));
		}
		//print_r($widthMax);
		foreach ($this->data as $row) {
			foreach ($this->thes as $field => $name) {
				$value = ifsetor($row[$field]);
				$value = is_array($value)
					? json_encode($value, JSON_PRETTY_PRINT)
					: strip_tags($value);
				$widthMax[$field] = max($widthMax[$field], mb_strlen($value));
				$widthAvg[$field] = ifsetor($widthAvg[$field]) + mb_strlen($value);
			}
		}
		if ($useAvg) {
			foreach ($this->thes as $field => $name) {
				$widthAvg[$field] /= sizeof($this->data);
				//$avgLen = round(($widthMax[$field] + $widthAvg[$field]) / 2);
				$avgLen = $widthAvg[$field];
				$widthMax[$field] = max(8, 1 + $avgLen);
			}
		}
		//print_r($widthMax);

		$dataWithHeader = array_merge(
			[$this->getThesNames()],
			$this->data,
			[$this->footer]
		);

		$content = "\n";
		foreach ($dataWithHeader as $row) {
			$padRow = [];
			foreach ($this->thes as $field => $name) {
				$value = ifsetor($row[$field]);
				$value = is_array($value)
					? json_encode($value, JSON_PRETTY_PRINT)
					: strip_tags($value);
				if ($cutTooLong) {
					$value = substr($value, 0, $widthMax[$field]);
				}
				$value = str_pad($value, $widthMax[$field], ' ', STR_PAD_RIGHT);
				$padRow[] = $value;
			}
			$content .= implode(" ", $padRow) . "\n";
		}
		return $content;
	}

	public function __toString()
	{
		return $this->render();
	}

	public function getThesNames()
	{
		$names = [];
		foreach ($this->thes as $field => $thv) {
			if (is_array($thv)) {
				$thvName = isset($thv['name'])
					? $thv['name']
					: (isset($thv['label']) ? $thv['label'] : '');
			} else {
				$thvName = $thv;
			}
			$names[$field] = $thvName;
		}
		return $names;
	}

}
