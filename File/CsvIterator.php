<?php

/**
 * Class CsvIterator
 * http://de2.php.net/fgetcsv#57802
 */
class CsvIterator implements Iterator, Countable
{

	public $filename;

	const ROW_SIZE = 4194304; // 4096*1024;

	/**
	 * The pointer to the cvs file.
	 * @var resource
	 * @access private
	 */
	public $filePointer = null;

	/**
	 * The current element, which will
	 * be returned on each iteration.
	 * @var array
	 * @access private
	 */
	protected $currentElement = null;

	/**
	 * @var int - cached amount of rows in a file
	 */
	protected $numRows;

	/**
	 * The row counter.
	 * @var int
	 * @access private
	 */
	public $rowCounter = 0;

	/**
	 * The delimiter for the csv file.
	 * @var string
	 * @access private
	 */
	private $delimiter = null;

	public $doConvertToUTF8 = false;

	protected $lastRead = -1; // != 0

	public $enclosure = '"';

	public $escape = '\\';

	/**
	 * This is the constructor.It try to open the csv file.The method throws an exception
	 * on failure.
	 *
	 * @access public
	 * @param string $filename The csv file.
	 * @param string $delimiter The delimiter.
	 *
	 * @throws Exception
	 */
	public function __construct($filename, $delimiter = ',')
	{
		$this->filename = $filename;
		ini_set('auto_detect_line_endings', true);
		$this->delimiter = $delimiter;
		try {
			$this->filePointer = $this->fopen_utf8($filename, 'r');
		} catch (Exception $e) {
			throw new Exception('The file "' . $filename . '" cannot be read because ' . $e->getMessage());
		}
	}

	/**
	 * Reads past the UTF-8 bom if it is there.
	 * @param string $filename
	 * @param string $mode
	 * @return resource
	 */
	public function fopen_utf8($filename, $mode)
	{
		$file = fopen($filename, $mode);
		if (!$file) {
			throw new InvalidArgumentException($filename . ' is not found in ' . getcwd());
		}
		$bom = fread($file, 3);
		if ($bom != b"\xEF\xBB\xBF") {
			rewind($file);
		} else {
			//echo "bom found!\n";
		}
		if (pathinfo($filename, PATHINFO_EXTENSION) == 'bz2') {
			stream_filter_prepend($file, 'bzip2.decompress', STREAM_FILTER_READ);
		}
		return $file;
	}

	/**
	 * This method resets the file pointer.
	 *
	 * @access public
	 */
	public function rewind(): void
	{
		$this->rowCounter = 0;
		// feof() is stuck in true after rewind somehow
		if (pathinfo($this->filename, PATHINFO_EXTENSION) == 'bz2') {
			$this->filePointer = $this->fopen_utf8($this->filename, 'r');
		} else {
			rewind($this->filePointer);
		}
		assert(ftell($this->filePointer) == 0);
		assert(!$this->feof());
		$this->lastRead = -1;
	}

	/**
	 * This method returns the current csv row as a 2-dimensional array
	 *
	 * @access public
	 * @return array The current csv row as a 2-dimensional array
	 */
	public function current(): mixed
	{
		$this->read();
		return $this->currentElement;
	}

	/**
	 * This method returns the current row number.
	 *
	 * @access public
	 * @return int The current row number
	 */
	public function key(): mixed
	{
		return $this->rowCounter;
	}

	public function feof(): bool
	{
		return feof($this->filePointer);
	}

	/**
	 * @access public
	 * @inheritdoc Returns the array value in the next place that's pointed to by the internal array pointer, or FALSE if there are no more elements.
	 * @return array|bool Returns FALSE on EOF reached, VALUE otherwise.
	 */
	public function next(): void
	{
		$this->rowCounter++;    // this make read() to read next row
		$this->read();
		if (!$this->currentElement) {
			//debug($this->feof(), ftell($this->filePointer));
		}
	}

	/**
	 * This method checks if the next row is a valid row.
	 *
	 * @access public
	 * @return bool If the next row is a valid row.
	 */
	public function valid(): bool
	{
		return !$this->feof();
	}

	/**
	 * If called multiple times should return the same value
	 * until next() is called
	 */
	public function read()
	{
		//debug_pre_print_backtrace();
		if ($this->rowCounter != $this->lastRead) {
			$this->currentElement = fgetcsv($this->filePointer, self::ROW_SIZE,
				$this->delimiter, $this->enclosure, $this->escape);

			if ($this->currentElement && $this->doConvertToUTF8) {
				$this->currentElement = array_map('utf8_encode', $this->currentElement);
			}

			//debug($this->currentElement);
			$this->lastRead = $this->rowCounter;
//			debug(__METHOD__, $this->rowCounter, $this->lastRead, first($this->currentElement));
		}
	}

	/**
	 * TODO: this is working, but will crash the rest of the reading!
	 * Count elements of an object
	 * @link http://php.net/manual/en/countable.count.php
	 * @return int The custom count as an integer.
	 * </p>
	 * <p>
	 * The return value is cast to an integer.
	 * @since 5.1.0
	 */
	public function count(): int
	{
		if ($this->numRows) {
			return $this->numRows;
		}
//		$save = ftell($this->filePointer);
//		$saveRow = $this->rowCounter;

		$count = 0;
		while ($this->valid()) {
			$this->next();
			$count++;
		}
		$this->numRows = $count;

//		$fail = fseek($this->filePointer, $save);
//		debug('fseek', $fail, $save);
		$this->rewind();
//		debug('fseek', ftell($this->filePointer), $this->feof());
		//$this->rowCounter = $saveRow;

		return $this->numRows;
	}

}
