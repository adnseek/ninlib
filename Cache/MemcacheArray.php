<?php

/**
 * Initialized with a file parameter and represents a file which stores an array of values.
 * Each get/set will work with one value from this array
 * Class MemcacheArray
 */
class MemcacheArray implements ArrayAccess
{

	/**
	 * @var string
	 */
	public $file;

	/**
	 * @var int
	 */
	protected $expire;

	/**
	 * @var MemcacheFile
	 */
	public $fc;

	/**
	 * @var mixed
	 */
	public $data;

	protected $state;

	public static $instances = [];

	/**
	 * @var callable
	 */
	public $onDestruct;            // callback

	/**
	 * @var int
	 */
	public $hit = 0;

	/**
	 * @var int
	 */
	public $miss = 0;

	/**
	 * Will show a line on the screen every time this is used.
	 * Useful to debug cache issues.
	 * @var bool
	 */
	static public $debug = false;

	/**
	 * @param string $file - filename inside /cache/ folder
	 * @param int $expire - seconds to keep the cache active
	 */
	public function __construct($file, $expire = 0)
	{
		TaylorProfiler::start(__METHOD__ . ' (' . $file . ')');
		//debug(__METHOD__.' ('.$file.')');
		$this->file = $file;
		$this->expire = $expire instanceof Duration ? $expire->getTimestamp() : $expire;
		$this->fc = new MemcacheFile();
		$this->data = $this->fc->get($this->file, $this->expire);
		if (!is_array($this->data)) {
			$this->data = [];
		}
		//debug($file);		debug_pre_print_backtrace();
		$this->state = serialize($this->data);
		if (self::$debug) {
			echo __METHOD__ . '(' . $file . ', ' . $expire . '). Sizeof: ' . sizeof($this->data) . BR;
		}
		TaylorProfiler::stop(__METHOD__ . ' (' . $file . ')');
	}

	/**
	 * Saving always means that the expiry date is renewed upon each read
	 * Modified to save only on changed data
	 */
	public function __destruct()
	{
		TaylorProfiler::start(__METHOD__);
		if ($this->onDestruct) {
			call_user_func($this->onDestruct, $this);
		}
		$this->save();
		//debug(sizeof($this->data));
		TaylorProfiler::stop(__METHOD__);
	}

	public function save()
	{
		if (false) {
			print_r($this->file);
			echo BR;
			debug_pre_print_backtrace();
			pre_print_r(array_keys($this->data));
			//echo '<pre>'; var_dump($this->data); echo '</pre>';
			//serialize($this->data);
		}
		$serialized = serialize($this->data);
		if ($this->fc && strcmp($this->state, $serialized)) {
			//debug(__METHOD__, $this->fc->map($this->file), sizeof($this->data), array_keys($this->data));
			$this->fc->set($this->file, $this->data);
		}
	}

	public function clearCache()
	{
		TaylorProfiler::start(__METHOD__);
		$prev = sizeof(self::$instances);
		$prevKeys = array_keys(self::$instances);
		self::unsetInstance($this->file);
		$curr = sizeof(self::$instances);
		//debug(__METHOD__, $this->file, $prev, $curr, $prevKeys, array_keys(self::$instances));
		$this->fc->clearCache($this->file);
		TaylorProfiler::stop(__METHOD__);
	}

	public function offsetSet($offset, $value): void
	{
		$this->data[$offset] = $value;
	}

	public function exists($key): bool
	{
		return isset($this->data[$key]);
	}

	public function get($key)
	{
		return $this->data[$key] ?? null;
	}

	/**
	 * __destruct should save
	 * @param string $key
	 * @param mixed $value
	 */
	public function set($key, $value): void
	{
		$this->data[$key] = $value;
	}

	public function offsetExists($offset): bool
	{
		return isset($this->data[$offset]);
	}

	public function offsetUnset($offset): void
	{
		unset($this->data[$offset]);
	}

	public function offsetGet($offset): mixed
	{
		return $this->data[$offset] ?? null;
	}

	public static function getInstance($file, $expire = 0)
	{
		if (self::$debug) {
			//echo __METHOD__.'('.$file.')'.BR;
		}
		return self::$instances[$file] ?? (self::$instances[$file] = new self($file, $expire));
	}

	public static function unsetInstance($file)
	{
		if (ifsetor(self::$instances[$file])) {
			if (ifsetor(self::$instances[$file]->fc)) {
				self::$instances[$file]->fc->clearCache(self::$instances[$file]->file);
			}
			self::$instances[$file]->__destruct();
		}
		unset(self::$instances[$file]);
	}

	public static function enableDebug()
	{
		self::$debug = true;
	}

}
