<?php

namespace nadlib\HTTP;

use Request;

class Session implements SessionInterface
{

	public $prefix;

	public function __construct($prefix = null)
	{
		$this->prefix = $prefix;
		if (!self::isActive()) {
			$this->start();
		}
	}

	public static function isActive()
	{
		//debug(session_id(), !!session_id(), session_status(), $_SESSION['FloatTime']);
		if (function_exists('session_status')) {
			// somehow PHP_SESSION_NONE is the status when $_SESSION var exists
			// PHP_SESSION_NONE removed as it's a major problem
			return session_status() === PHP_SESSION_ACTIVE;
		}

		return !!session_id() && isset($_SESSION);
	}

	public function start()
	{
		if (!Request::isPHPUnit() && !Request::isCLI()) {
			if (!headers_sent()) {
				// not using @ to see when session error happen
				llog('session_start in Session');
				session_start();
			}
		}
	}

	public static function make($prefix)
	{
		return new self($prefix);
	}

	public function get($key, $default = null)
	{
		if (is_callable($default)) {
			$default = $default();
		}

		if ($this->prefix) {
			return ifsetor($_SESSION[$this->prefix][$key], $default);
		}

		return ifsetor($_SESSION[$key], $default);
	}

	public function getOnce($key)
	{
		$value = $this->get($key);
		$this->delete($key);
		return $value;
	}

	public function delete($string)
	{
		if ($this->prefix) {
			unset($_SESSION[$this->prefix][$string]);
		} else {
			unset($_SESSION[$string]);
		}
	}

	public function set($key, $val)
	{
		$this->save($key, $val);
	}

	public function save($key, $val)
	{
		if ($this->prefix) {
			$_SESSION[$this->prefix][$key] = $val;
		} else {
			$_SESSION[$key] = $val;
		}
	}

	public function __get($name)
	{
		return $this->get($name);
	}

	public function __set($name, $value)
	{
		$this->save($name, $value);
	}

	public function __isset($name)
	{
		return $this->get($name);
	}

	public function clearAll()
	{
		unset($_SESSION[$this->prefix]);
	}

	public function has($key)
	{
		return $this->prefix
			? isset($_SESSION[$this->prefix][$key])
			: ifsetor($_SESSION[$key]);
	}

	public function append($key, $val)
	{
		if ($this->prefix) {
			$_SESSION[$this->prefix][$key][] = $val;
		} else {
			$_SESSION[$key][] = $val;
		}
	}

	public function getKeys()
	{
		return array_keys($this->getAll());
	}

	public function getAll()
	{
		return $this->prefix ? ifsetor($_SESSION[$this->prefix], []) : $_SESSION;
	}

}
