<?php

/**
 * @phpstan-consistent-constructor
 */
class CsvIteratorWithHeader extends CsvIterator
{

	/**
	 * @var array
	 */
	public $columns;

	public function __construct($filename, $delimiter = ',', $convertUTF8 = false)
	{
		parent::__construct($filename, $delimiter);
		$this->doConvertToUTF8 = $convertUTF8;
		$this->columns = parent::current();    // first line
//		$this->current();	// this should not be next();
		$this->next();
		//print_r($this->columns);
	}

	public function current(): mixed
	{
		parent::current();
		//debug($this->columns, $this->currentElement);
		if ($this->currentElement) {
			if (sizeof($this->currentElement) != sizeof($this->columns)) {
				debug($this->currentElement, $this->columns);
			}
			$this->currentElement = array_combine($this->columns, $this->currentElement);
		}
		return $this->currentElement;
	}

	public function next(): void
	{
		parent::next();
		//debug($this->columns, $this->currentElement);
		$return = $this->current();
		if ($return !== false && $this->currentElement) {
			if (sizeof($this->currentElement) == sizeof($this->columns)) {
				$this->currentElement = array_combine($this->columns, $this->currentElement);
			}
		}
	}

	public function rewind(): void
	{
		parent::rewind();
		$this->next();    // skip header row again
		$this->next();    // jump to the next row
//		debug(__METHOD__, $this->current());
	}

	public function ftell()
	{
		return ftell($this->filePointer);
	}

}
