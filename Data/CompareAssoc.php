<?php

class CompareAssoc
{

	public $keys;

	public $assoc1;

	public $assoc2;

	/**
	 * @var array[]
	 */
	public $table;

	public function __construct($assoc1, $assoc2)
	{
		$this->keys = array_keys($assoc1) + array_keys($assoc2);
		$this->assoc1 = $assoc1;
		$this->assoc2 = $assoc2;
		$this->compare();
	}

	public function compare()
	{
		foreach ($this->keys as $key) {
			$this->table[$key] = [
				'key' => $key,
				'assoc1' => $this->assoc1[$key],
				'assoc2' => $this->assoc2[$key],
				'same' => $this->assoc1[$key] == $this->assoc2[$key],
				'identical' => $this->assoc1[$key] === $this->assoc2[$key],
			];
		}
	}

	public function render()
	{
		return new slTable($this->table);
	}

	public function isDifferent()
	{
		return array_reduce($this->table, function ($acc, $row) {
			return $acc + $row['same'];
		}, 0);
	}

}
