<?php

/**
 * Usage:
 * $source = array(
 *        array(    // row 1
 *            'col1' => 'val1',
 *            'col2' => 'val2',
 *        ),
 *        'row2' => array(
 *            'col1' => 'val3',
 *            'col2' => 'val4',
 *        ),
 * );
 * $ap = new ArrayPlus($source);
 * $column = $ap->column('col2');
 *
 * $column = array(
 *        '0' => 'val2',
 *        'row2' => 'val4',
 * );
 *
 * class ArrayPlus
 * Rules:
 * 1. Don't access $this->data, use $this as an array
 * 2. Don't access by reference - data is not updated.
 * http://stackoverflow.com/questions/8685186/arrayaccess-in-php-assigning-to-offset-by-reference
 * 3. Don't set $this->data = $new, use $this->setData($new)
 */

class ArrayPlus extends ArrayObject implements Countable
{

	public function __construct(array $array = [])
	{
		parent::__construct($array);
		$this->setData($array);
	}

	/**
	 * Chainable
	 *
	 * @param array $data
	 * @return static
	 */
	public function setData(array $data)
	{
		$this->exchangeArray($data);
		return $this;
	}

	/**
	 * http://php.net/manual/en/function.array-splice.php#111204
	 * @param array $input
	 * @param int $offset - key of the element to insert BEFORE(!)
	 * @param int $length
	 * @param array $replacement
	 * @return array
	 */
	public static function array_splice_assoc(&$input, $offset, $length, $replacement = [])
	{
		$replacement = (array)$replacement;
		$key_indices = array_flip(array_keys($input));
		if (isset($input[$offset]) && is_string($offset)) {
			$offset = $key_indices[$offset];
		}
		if (isset($input[$length]) && is_string($length)) {
			$length = $key_indices[$length] - $offset;
		}

		$extract = array_slice($input, $offset, $length, true);

		$input = array_slice($input, 0, $offset, true)
			+ $replacement
			+ array_slice($input, $offset + $length, null, true);
		return $extract;
	}

	public static function isRecursive(array $array)
	{
		foreach ($array as $item) {
			if (is_array($item)) {
				return true;
			}
		}
		return false;
	}

	public function column_coalesce($col1, $col2): int
	{
		$return = [];
		foreach ((array)$this as $key => $row) {
			$return[$key] = ifsetor($row[$col1]) ? $row[$col1] : $row[$col2];
		}
		return $return;
	}

	public function pluck($key)
	{
		return $this->column($key);
	}

	/**
	 * Returns an array of the elements in a specific column.
	 * @param string $col
	 * @return ArrayPlus
	 */
	public function column($col)
	{
		$return = [];
		foreach ((array)$this as $key => $row) {
			$return[$key] = ifsetor($row[$col]);
		}
		$ap = new ArrayPlus($return);
		return $ap;
	}

	public function pick($key)
	{
		return $this->getMap(function ($el) use ($key) {
			//			debug($el, $key);
			return $el->$key;
		});
	}

	/**
	 * @param callable $callback
	 * @return array
	 */
	public function getMap($callback)
	{
		return array_map($callback, $this->getData());
	}

	/**
	 * @return array
	 */
	public function getData()
	{
		return (array)$this;
	}

	public function pickAssoc($name, $key = 'id')
	{
		$keys = $this->getMap(function ($el) use ($key) {
			return $el->$key;
		});
		$names = $this->getMap(function ($el) use ($name) {
			return $el->$name;
		});
		return array_combine($keys, $names);
	}

	public function column_assoc($key, $val)
	{
		$data = [];
		foreach ((array)$this as $row) {
			$data[$row[$key]] = $row[$val];
		}
		$this->setData($data);
		return $this;
	}

	/**
	 * Use to filter input array to keep some data that fits the DB schema
	 * @param array $keep
	 * @return $this
	 */
	public function keepColumns(array $keep)
	{
		$data = [];
		foreach ((array)$this as $i => $row) {
			$row = array_intersect_key($row, array_combine($keep, $keep));
			$data[$i] = $row;
		}
		$this->setData($data);
		return $this;
	}

	/**
	 * Use to filter a set of data into a different set of keys
	 * @param array $keep ['new' => 'existing'] it's reversed, keys are what you want to have
	 * @return $this
	 */
	public function remap(array $keep)
	{
		$data = [];
		foreach ($keep as $i => $row) {
			if (isset($this[$row])) {    // don't make empty SQL UPDATE SET a = NULL
				$data[$i] = $this[$row];
			}
		}
		$this->setData($data);
		return $this;
	}

	public function remapColumns(array $keys)
	{
		$data = $this->getData();
		foreach ($data as &$row) {
			$row = array_combine($keys, $row);
		}
		$this->setData($data);
		return $this;
	}

	/**
	 * Modifies itself
	 * @param string $key
	 * @param bool $allowMerge
	 * @return ArrayPlus
	 * @throws Exception
	 */
	public function IDalize($key = 'id', $allowMerge = false)
	{
		$data = [];
		foreach ($this as $row) {
			$keyValue = $row[$key];
			if (!$keyValue && !$allowMerge) {
				$error = __METHOD__ . '#' . __LINE__ . ' You may need to specify $this->idField in your model.';
				debug([
					'error' => $error,
					'key' => $key,
					'row' => $row,
					'data' => $this->getData(),
				]);
				throw new Exception($error);
			}
			$data[$keyValue] = $row;
		}
		$this->setData($data);
		return $this;
	}

	/**
	 * [
	 *  ['a' => 'b'],
	 *  ['a' => 'c']
	 * ] ---> [
	 *  ['a' => ['b', 'c']]
	 * ]
	 * @param string $key
	 * @return $this
	 */
	public function combine($key = 'id')
	{
		$data = [];
		foreach ($this as $row) {
			$keyValue = $row[$key];
			$data[$keyValue][] = $row;
		}
		$this->setData($data);
		return $this;
	}

	#[\ReturnTypeWillChange]
	public function append(mixed $value, $key = null)
	{
		if (!is_null($key)) {
			$this[$key] = $value;
		} else {
			$this[] = $value;
		}
		return $this;
	}

	/**
	 * Callback = function ($value, [$index]) {}
	 *
	 * @param callable $callback
	 * @return static
	 */
	public function each($callback)
	{
		foreach ($this as $i => $el) {
			//$el = $callback($el, $i);
			$el = call_user_func($callback, $el, $i);
			$this[$i] = $el;
		}
		return $this;
	}

	/**
	 * Callback = function ($value, [$index]) {}
	 *
	 * @param callable $callback
	 * @return string
	 */
	public function eachCollect($callback)
	{
		$content = '';
		foreach ($this as $i => $el) {
			$plus = $callback($el, $i);
			$content .= $plus;
		}
		return $content;
	}

	public function ksort(int $flags = SORT_REGULAR): bool
	{
		$arrayCopy = $this->getArrayCopy();
		ksort($arrayCopy);
		$this->setData($arrayCopy);
		return $this;
	}

	/**
	 * Returns the first found row
	 * @param string $key
	 * @param mixed $val
	 * @return mixed
	 */
	public function searchColumn($key, $val)
	{
		foreach ($this as $row) {
			if ($row[$key] == $val) {
				return $row;
			}
		}
	}

	public function getAssoc($key, $val)
	{
		$ret = [];
		foreach ($this as $row) {
			$ret[$row[$key]] = $row[$val];
		}
		return $ret;
	}

	/**
	 * @return static
	 */
	public function trim()
	{
		foreach ($this as $i => $value) {
			$this[$i] = trim($value);
		}
		return $this;
	}

	public function stripTags()
	{
		foreach ($this as $i => $value) {
			$this[$i] = strip_tags($value);
		}
		return $this;
	}

	/**
	 * Will keep the assoc keys
	 * @param callable $callback
	 * @return static
	 */
	public function mapBoth($callback)
	{
		$data = $this->getData();
		$keys = $this->getKeys();
		$mapped = array_map(function ($key) use ($callback, $data) {
			return $callback($key, $data[$key]);
		}, $keys->getData());
		$mapped = array_combine($keys->getData(), $mapped);
		$this->setData($mapped);
		return $this;
	}

	public function getKeys()
	{
		return new self(array_keys($this->getData()));
	}

	/**
	 * @param string $a
	 * @param string $b
	 * @return array
	 */
	public function wrap($a, $b)
	{
		foreach ($this as $i => $value) {
			$this[$i] = $a . $value . $b;
		}
		return $this->getData();
	}

	/**
	 * Not working as expected.
	 * PHP 4 doesn't have seek() function
	 * PHP 5 doesn't have prev() method
	 * @param string $key
	 */
	public function getPrevNext($key)
	{
		$row = $this->where('id', $key);
		$row2 = $this[$key];    // works, but how to get next?
		# http://stackoverflow.com/questions/4792673/php-get-previous-array-element-knowing-current-array-key
		# http://www.php.net/manual/en/arrayiterator.seek.php
		$arrayobject = new ArrayObject($this);
		$iterator = $arrayobject->getIterator();

		if ($iterator->valid()) {
			$iterator->seek(array_search($key, array_keys((array)$this)));
			$row3 = $iterator->current();
			$iterator->next();
			$next = $iterator->current();
			$iterator->previous();
			$iterator->previous();
			$prev = $iterator->current();
		}
//		debug($key, $row, $row2, $row3['id'], $next['id'], $prev['id']); //, $rc->data);//, $rc);
	}

	/**
	 * @param array $where
	 * @return mixed - single row
	 * @throws Exception
	 */
	public function findInData(array $where)
	{
		//debug($where);
		//echo new slTable($this->data);
		foreach ($this->getData() as $row) {
			$intersect1 = array_intersect_key($row, $where);
			$intersect2 = array_intersect_key($where, $row);
			if ($intersect1 == $intersect2) {
				return $row;
			}
		}
		return null;
	}

	/**
	 * http://stackoverflow.com/a/9944080/417153
	 * @param string $key
	 * @return bool
	 */
	public function getPrevKey($key)
	{
		$keys = array_keys($this->getData());
		$found_index = array_search($key, $keys);
		if ($found_index === false || $found_index === 0) {
			return false;
		}
		return $keys[$found_index - 1];
	}

	/**
	 * http://stackoverflow.com/a/9944080/417153
	 * @param string $key
	 * @return bool
	 */
	public function getNextKey($key)
	{
		$keys = array_keys($this->getData());
		$found_index = array_search($key, $keys);
		if ($found_index === false || $key == end($keys)) {
			return false;
		}
		return $keys[$found_index + 1];
	}

	/**
	 * Searches inside Recursive tree
	 * @param string $needle
	 * @return array|null
	 */
	public function find($needle)
	{
		foreach ($this as $key => $val) {
			//debug($needle, $key, $val);
			if ($val instanceof Recursive) {
				$sub = new ArrayPlus($val->getChildren());
				$find = $sub->find($needle);
				//$find = $val->findPath($)
				if ($find) {
					//debug($needle, $key, $find);
					array_unshift($find, $key);
					//debug($find);
				}
			} else {
				$find = ($key == $needle) ? [$key] : null;
			}
			if ($find) {
				return $find;
			}
		}
	}

	public function findAlternativeFromMenu($current)
	{
		foreach ($this->getData() as $key => $rec) {
			/** @var $rec Recursive */
			//$found = $rec->findPath($this->current);
			if ($rec instanceof Recursive) {
				$children = $rec->getChildren();
				$found = $children[$current] ?? null;
				//debug($children, $found, $key, $this->current);
				return $found;
			}
		}
		return null;
	}

	public function first()
	{
		if (!$this->count()) {
			return null;
		}
		$var = $this->getData();
		reset($var);
		return current($var);
	}

	public function count(): int
	{
		return parent::count();
	}

	/**
	 * Bug. Only one element per sorted field is allowed.
	 * @param string $column
	 * @return $this
	 */
	public function sortBy($column)
	{
		$this->insertKeyAsColumn();
		// buggy
		//$this->IDalize($column, true);	// allow merge
		//$this->ksort();

		// correct
		$copy = clone $this;
		$sortCol = $copy->column($column)->getData();
		$aCopy = $this->getData();
		array_multisort($sortCol, $aCopy);
		// Associative (string) keys will be maintained, but numeric keys will be re-indexed.
		$this->exchangeArray($aCopy);
		$this->extractKeyFromColumn();
		return $this;
	}

	/**
	 * Enters array key as ['__key__']
	 * @param string $keyColumnName
	 * @return $this
	 */
	public function insertKeyAsColumn($keyColumnName = '__key__')
	{
		foreach ($this->getData() as $key => $_) {
			$this[$key][$keyColumnName] = $key;
		}
		return $this;
	}

	/**
	 * Extracts key from array as ['__key__']
	 * @param string $column
	 * @param bool $unset
	 * @return ArrayPlus
	 */
	public function extractKeyFromColumn($column = '__key__', $unset = true)
	{
		$new = [];
		foreach ($this as $row) {
			$key = $row[$column];
			if ($unset) {
				unset($row[$column]);
			}
			$new[$key] = $row;
		}
		$this->setData($new);
		return $this;
	}

	public function transpose()
	{
		$out = [];
		foreach ($this as $key => $subarr) {
			foreach ($subarr as $subkey => $subvalue) {
				$out[$subkey][$key] = $subvalue;
			}
		}
		$this->setData($out);
		return $this;
	}

	/**
	 * @param array $column
	 * @return static
	 */
	public function unshift(array $column)
	{
		reset($column);
		foreach ($this as $i => $row) {
			$this[$i] = [current($column)] + $row;
			next($column);
		}
		return $this;
	}

	public function min()
	{
		if ($this->count()) {
			return min($this->getData());
		} else {
			return null;
		}
	}

	public function max()
	{
		if ($this->count()) {
			return max($this->getData());
		} else {
			return null;
		}
	}

	public function avg()
	{
		$count = $this->count();
		if ($count != 0) {
			return $this->sum() / $count;
		}
	}

	public function sum()
	{
		return array_sum($this->getData());
	}

	/**
	 * Runs get_object_vars() recursively
	 * @param array $data
	 * @return ArrayPlus
	 */
	public function object2array(array $data = null)
	{
		$this->setData($this->objectToArray($this));
		return $this;
	}

	/**
	 * http://www.if-not-true-then-false.com/2009/php-tip-convert-stdclass-object-to-multidimensional-array-and-convert-multidimensional-array-to-stdclass-object/
	 * @param object $d
	 * @return array
	 */
	protected function objectToArray($d)
	{
		if (is_object($d)) {
			// Gets the properties of the given object
			// with get_object_vars function
			$d = get_object_vars($d);
		}

		if (is_array($d)) {
			/*
			* Return array converted to object
			* Using __FUNCTION__ (Magic constant)
			* for recursive call
			*/
			return array_map([$this, __FUNCTION__], $d);
		} else {
			// Return array
			return $d;
		}
	}

	/**
	 *    $tree = AP(array('a' => array('b' => array('c' => 'd'))));
	 * $linear = $tree->linearize();
	 * return slTable::showAssoc($linear, true, true);
	 * == "c": "d"
	 * @param array $data
	 * @return array
	 */
	public function linearize(array $data = null)
	{
		$data = $data ? $data : $this;
		$linear = [];
		foreach ($data as $key => $val) {
			if (is_array($val) && $val) {
				$linear = array_merge($linear, $this->linearize($val));
			} else {
				$linear[$key] = $val;
			}
		}
		return $linear;
	}

	public function filterBoth($callback = null)
	{
		if ($callback /*is_callable($callback)*/) {
			$new = array_filter($this->getData(), $callback, ARRAY_FILTER_USE_BOTH);
		} else {
			$new = array_filter($this->getData());
		}
		$this->setData($new);
		return $this;
	}

	public function filterContains($needle)
	{
		return $this->filter(function ($el) use ($needle) {
			return str_contains($el, $needle);
		});
	}

	public function filter($callback = null)
	{
		if ($callback /*is_callable($callback)*/) {
			$new = array_filter($this->getData(), $callback);
		} else {
			$new = array_filter($this->getData());
		}
		$this->setData($new);
		return $this;
	}

	public function implode($sep = "\n")
	{
		return implode($sep, $this->getData());
	}

	public function typoscript($prefix = '')
	{
		$replace = [];
		foreach ($this as $key => $val) {
			$prefixKey = $prefix ? $prefix . '.' . $key : $key;
			if (is_array($val)) {
				$plus = self::create($val)->typoscript($prefixKey);
				$replace += $plus;
			} else {
				$replace[$prefixKey] = $val;
			}
		}
		return $replace;
	}

	public function concat()
	{
		return implode('', $this->getData());
	}

	public function count_if($k)
	{
		$count = 0;
		foreach ($this as $val) {
			if (ifsetor($val[$k])) {
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Searches table for specific columns and counts where $k2 is true
	 * @param array $k1s
	 * @param       $k2
	 * @return int
	 */
	public function count_if_sub(array $k1s, $k2)
	{
		$count = 0;
		foreach ($this as $val) {
			foreach ($val as $key2 => $val2) {
				if (in_array($key2, $k1s) && $val2[$k2]) {
					$count++;
					break;
				}
			}
		}
		return $count;
	}

	/**
	 * Chainable
	 *
	 * @param string $i
	 * @param mixed $val
	 * @return $this
	 */
	public function set($i, $val)
	{
		$this->offsetSet($i, $val);
		return $this;
	}

	/**
	 * Chainable
	 *
	 * @param mixed $i
	 * @return static
	 */
	public function un_set($i)
	{
		$this->offsetUnset($i);
		return $this;
	}

	public function get($i, $subkey = null)
	{
		$element = $this->offsetGet($i);
		if ($subkey) {
			$element = $element[$subkey];
		}
		return $element;
	}

	public function debug()
	{
		return [
			'class' => get_class($this),
			'count' => $this->count(),
		];
	}

	/**
	 * @param string $oldKey
	 * @param string $newKey
	 * @return static
	 * @throws Exception
	 */
	public function replace_key($oldKey, $newKey)
	{
		$keys = array_keys((array)$this);
		if (false === $index = array_search($oldKey, $keys)) {
			throw new Exception(sprintf('Key "%s" does not exit', $oldKey));
		}
		$keys[$index] = $newKey;
		$this->exchangeArray(array_combine($keys, array_values((array)$this)));
		return $this;
	}

	/**
	 * @param array $ar2
	 * @return static
	 */
	public function merge_recursive_overwrite($ar2)
	{
		foreach ($ar2 as $key2 => $val2) {
			if (isset($this[$key2]) && is_array($this[$key2])) {
				$tmp = self::create($this[$key2]);
				$tmp->merge_recursive_overwrite($val2);
				$this[$key2] = $tmp->getData();
			} else {
				$this[$key2] = $val2;
			}
		}
		return $this;
	}

	/**
	 * 2D table => 3D table
	 * @param string $groupBy
	 * @return $this
	 */
	public function groupBy($groupBy)
	{
		$new = [];
		foreach ($this->getData() as $line) {
			$key = $line[$groupBy];
			$new[$key][] = $line;
		}
		$this->setData($new);
		return $this;
	}

	public function sumGroups($field)
	{
		$new = new ArrayPlus();
		foreach ($this->getData() as $key => $subtable) {
			$ap = ArrayPlus::create($subtable);
			$new[$key] = $ap->column($field)->sum();
		}
		return $new;
	}

	/**
	 * Static initializers can be chained in PHP
	 * @param array $data
	 * @return ArrayPlus
	 */
	public static function create(array $data = [])
	{
		$self = new self($data);
		return $self;
	}

	public function columnEmpty($string)
	{
		foreach ($this->getData() as $row) {
			if (ifsetor($row[$string])) {
				return false;
			}
		}
		return true;
	}

	public function columnSet($string)
	{
		foreach ($this->getData() as $row) {
			if (isset($row[$string])) {
				return true;
			}
		}
		return false;
	}

	public function findDelete($niceName)
	{
		$ar = $this->getData();
		$index = array_search($niceName, $ar);
		if ($index !== false) {
			array_splice($ar, $index, 1);
			$this->setData($ar);
		}
		return $this;
	}

	public function replaceKeys(array $visibleFields)
	{
		foreach ($visibleFields as $key => $val) {
			$this->replace_key($key, $val);
		}
		return $this;
	}

	/**
	 * Used in HTTP protocol
	 * @param null $joinWith
	 * @return array|string
	 */
	public function getHeaders($joinWith = null)
	{
		$headers = [];
		foreach ($this as $key => $val) {
			$headers[] = $key . ': ' . $val;
		}
		if ($joinWith) {
			$headers = implode($joinWith, $headers);
		}
		return $headers;
	}

	/**
	 * If we store array of objects, we can retrieve a specific property of all objects
	 * @param string $name
	 * @return ArrayPlus
	 */
	public function getProperty($name)
	{
		$result = [];
		foreach ($this->getData() as $i => $object) {
			if (is_object($object)) {
				$result[$i] = $object->$name;
			}
		}
		return new ArrayPlus($result);
	}

	public function call($method)
	{
		$result = [];
		foreach ($this->getData() as $i => $object) {
			if (is_object($object)) {
				$result[$i] = $object->$method();
			}
		}
		$this->setData($result);
		return $this;
	}

	public function andAll()
	{
		$result = true;
		foreach ($this->getData() as $i => $object) {
			$result = $result && $object;
		}
		return $result;
	}

	public function orAll()
	{
		$result = false;
		foreach ($this->getData() as $i => $object) {
			$result = $result || $object;
		}
		return $result;
	}

	public function __toString()
	{
		return json_encode($this->getArrayCopy(), JSON_PRETTY_PRINT);
	}

	public function toStringEach()
	{
		$new = [];
		foreach ($this->getData() as $i => $mixed) {
			$new[$i] = (string)$mixed;
		}
		return $new;
	}

	public function combineSelf()
	{
		return $this->setData(array_combine(
			$this->getData(),
			$this->getData()
		));
	}

	public function stringArray()
	{
		$new = [];
		foreach ($this->getData() as $i => $mixed) {
			$new[$i] = MergedContent::mergeStringArrayRecursive($mixed);
		}
		$this->setData($new);
		return $this;
	}

	public function contains($string)
	{
		return in_array($string, $this->getData());
	}

	public function containsPartly($string)
	{
		foreach ($this as $element) {
			if (str_contains($element, $string)) {
				return true;
			}
		}
		return false;
	}

	public function convertTo($className)
	{
		foreach ($this as $key => $row) {
			if (method_exists($className, 'getInstance')) {
				$instance = $className::getInstance($row);
			} else {
				$instance = new $className($row);
			}
			$this[$key] = $instance;
		}
		return $this;
	}

	public function makeTable($newKey)
	{
		$copy = [];
		foreach ($this->getData() as $key => $row) {
			if (is_array($row)) {
				$copy[$key] = $row;
			} else {
				$copy[$key] = [
					$newKey => $row,
				];
			}
		}
		$this->setData($copy);
		return $this;
	}

	/**
	 * @return ArrayPlus
	 */
	public function sortByValue()
	{
		$data = $this->getData();
		asort($data);
		$this->setData($data);
		return $this;
	}

	public function sortByKey()
	{
		$data = $this->getData();
		ksort($data);
		$this->setData($data);
		return $this;
	}

	public function reverse()
	{
		$this->setData(array_reverse($this->getData()));
		return $this;
	}

	public function getSlice($from = 0, $length = null)
	{
		return array_slice($this->getData(), $from, $length, true);
	}

	public function addColumn($columnName, $callback)
	{
		$copy = $this->getData();
		foreach ($copy as $i => $row) {
			$copy[$i][$columnName] = call_user_func($callback, $row, $i);
		}
		$this->setData($copy);
		return $this;
	}

	/**
	 * @return ArrayPlus
	 */
	public function values()
	{
		$this->setData(array_values($this->getData()));
		return $this;
	}

	/**
	 * @param ArrayPlus|array $ap
	 * @return ArrayPlus
	 */
	public function diff($ap)
	{
		$new = clone($this);
		$ap = $ap instanceof ArrayPlus ? $ap->getData() : $ap;
		$new->setData(array_diff($new->getData(), $ap));
		return $new;
	}

	/**
	 * Filter rows where $key = $value
	 * @param string $key
	 * @param mixed $value
	 * @return $this
	 */
	public function where($key, $value)
	{
		$copy = $this->getData();
		foreach ($copy as $i => $row) {
			if ($row[$key] != $value) {
				unset($copy[$i]);
			}
		}
		// no setData() here
		return new self($copy);
	}

	/**
	 * Filter rows where $key = $value
	 * @param string $key
	 * @param mixed $value
	 * @return $this
	 */
	public function filterWhere($key, $value)
	{
		$copy = $this->getData();
		foreach ($copy as $i => $row) {
			if ($row[$key] != $value) {
				unset($copy[$i]);
			}
		}
		$this->setData($copy);
		return $this;
	}

	public function filterBy(array $where)
	{
		//		debug($where, sizeof($this->events));
		$this->setData(
			array_filter($this->getData(), function ($row) use ($where) {
				//			$same = array_intersect_key((array)$row, $where);

				$okList = [];
				foreach ($where as $k => $v) {
					if (is_object($v)) {
						//					var_dump($v);
					}
					if ($v instanceof FilterBetween) {
						$ok = $v->apply($row->$k);
					} elseif (is_array($v)) {
						$ok = in_array($row->$k, $v);
					} else {
						$value = is_object($row)
							? $row->$k
							: ifsetor($row[$k]);
						$ok = $v == $value;
					}
					$okList[$k] = $ok;
				}
				$okList = array_filter($okList);
				//			debug($where, $okList);
				return sizeof($okList) == sizeof($where);
			})
		);
		return $this;
	}

	public function apply(callable $fn)
	{
		$this->map($fn);
	}

	/**
	 * Keys are reindexed
	 * @param callable $callback
	 * @return static
	 */
	public function map($callback)
	{
		$this->setData(array_map($callback, $this->getData()));
		return $this;
	}

	public function has($el)
	{
		return in_array($el, $this->getArrayCopy());
	}

	public function __debugInfo(): array
	{
		return [
			'count' => $this->count(),
			'is_assoc' => self::has_string_keys($this->getArrayCopy()),
		];
	}

	/**
	 * http://stackoverflow.com/questions/173400/how-to-check-if-php-array-is-associative-or-sequential
	 * @param array $array
	 * @return bool
	 * @static because it's used in the constructor of VisibleColumns
	 */
	public static function has_string_keys(array $array)
	{
		return count(array_filter(array_keys($array), 'is_string')) > 0;
	}

	public function reindex(callable $keyGenerator)
	{
		$new = new ArrayPlus();
		foreach ($this as $key => $val) {
			$newKey = $keyGenerator($key, $val);
			$new[$newKey][] = $val;
		}
		return $new;
	}

	public function reindexOne(callable $keyGenerator)
	{
		$new = new ArrayPlus();
		foreach ($this as $key => $val) {
			$newKey = $keyGenerator($key, $val);
			$new[$newKey] = $val;
		}
		return $new;
	}

	public function countEach()
	{
		$set = [];
		foreach ($this as $key => $val) {
			$set[$key] = is_array($val) ? sizeof($val) : 1;
		}
		return $set;
	}

	public function insertBefore($key, $content)
	{
		$indexes = array_keys($this->getArrayCopy());
		$intPos = array_search($key, $indexes, true);
		if ($intPos) {
			$beforeKeys = array_slice($indexes, 0, $intPos);
		} else {
			$beforeKeys = [];
		}
		$values = array_values($this->getArrayCopy());
		$before = array_combine($beforeKeys, array_slice($values, 0, $intPos));
//		debug($indexes, $intPos, $beforeKeys, $before);
		// insert
		$before[] = $content;
		// add remaining
		$afterKeys = array_slice($indexes, $intPos);
		$together = array_merge($before, array_combine($afterKeys, array_slice($values, $intPos)));
		$this->setData($together);
		return $this;
	}

	// untested from https://stackoverflow.com/questions/3797239/insert-new-item-in-array-on-any-position-in-php
	public function insertAfter($position, $insert)
	{
		if (is_int($position)) {
			$copy = $this->getArrayCopy();
			array_splice($copy, $position, 0, $insert);
			$this->setData($copy);
		} else {
			$pos = array_search($position, array_keys($this->getArrayCopy()));
			if (false === $pos) {
				throw new Exception('position ' . $position . ' not found');
			}
			$array = array_merge(
				array_slice($this->getArrayCopy(), 0, $pos + 1),
				$insert,
				array_slice($this->getArrayCopy(), $pos + 1)
			);
			$this->setData($array);
		}
		return $this;
	}

	public function without(array $keys)
	{
		foreach ($keys as $key) {
			unset($this[$key]);
		}
		return $this;
	}

	public function sort($callback)
	{
		$data = $this->getArrayCopy();
		usort($data, $callback);
		$this->setData($data);
		return $this;
	}

	public function any(Closure $check)
	{
		foreach ($this->getData() as $el) {
			$ok = $check($el);
			if ($ok) {
				return true;
			}
		}
		return false;
	}

	public function all(Closure $check)
	{
		foreach ($this->getData() as $el) {
			$ok = $check($el);
			if (!$ok) {
				return false;
			}
		}
		return true;
	}

	public function none(Closure $check)
	{
		foreach ($this->getData() as $el) {
			$ok = $check($el);
			if ($ok) {
				return false;
			}
		}
		return true;
	}

	public function toArray()
	{
		return $this->getData();
	}
}

function AP($a = [])
{
	if ($a instanceof ArrayPlus) {
		return $a;
	} elseif (is_array($a)) {
		return ArrayPlus::create($a);
	} else {
		throw new InvalidArgumentException(__METHOD__ . ' accepts array');
	}
}
