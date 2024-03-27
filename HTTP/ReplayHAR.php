<?php

use spidgorny\nadlib\HTTP\URL;

class ReplayHAR implements Iterator
{

	public $file;

	public $har;

	public $request;

	public function __construct($file)
	{
		$this->file = $file;
		$this->readHAR();
	}

	public function readHAR()
	{
		$this->har = json_decode(file_get_contents($this->file));
		$this->current();
	}

	public function getURL()
	{
		if (!$this->request) {
			$this->readHAR();
		}
		$url = new URL($this->request->url);
		$url->clearParams();
		$url->setParamsFromHAR($this->request->queryString);
		return $url;
	}

	public function getURLGet()
	{
		$url = $this->getURL();
		$urlget = $url->getURLGet();
		$urlget->context['http']['method'] = $this->request->method;
		foreach ($this->request->headers as $pair) {
			$urlget->headers[$pair->name] = $pair->value;
		}
		return $urlget;
	}

	/**
	 * Return the current element
	 * @link http://php.net/manual/en/iterator.current.php
	 * @return mixed Can return any type.
	 * @since 5.0.0
	 */
	public function current(): mixed
	{
		$el = current($this->har->log->entries);
		$this->request = $el->request;
		return $this->request;
	}

	/**
	 * Move forward to next element
	 * @link http://php.net/manual/en/iterator.next.php
	 * @return void Any returned value is ignored.
	 * @since 5.0.0
	 */
	public function next(): void
	{
		$el = next($this->har->log->entries);
		$this->request = $el->request;
	}

	/**
	 * Return the key of the current element
	 * @link http://php.net/manual/en/iterator.key.php
	 * @return mixed scalar on success, or null on failure.
	 * @since 5.0.0
	 */
	public function key(): mixed
	{
		return key($this->har->log->entries);
	}

	/**
	 * Checks if current position is valid
	 * @link http://php.net/manual/en/iterator.valid.php
	 * @return bool The return value will be casted to boolean and then evaluated.
	 * Returns true on success or false on failure.
	 * @since 5.0.0
	 */
	public function valid(): bool
	{
		return !!($this->har->log->entries);
	}

	/**
	 * Rewind the Iterator to the first element
	 * @link http://php.net/manual/en/iterator.rewind.php
	 * @return void Any returned value is ignored.
	 * @since 5.0.0
	 */
	public function rewind(): void
	{
		rewind($this->har->log->entries);
	}

	public function last()
	{
		$el = end($this->har->log->entries);
		$this->request = $el->request;
		return $this->request;
	}

}
