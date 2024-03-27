<?php

class SQLWhere implements ArrayAccess
{

	/**
	 * @var DBInterface
	 */
	protected $db;

	protected $parts = [];

	public function __construct($where = null)
	{
		if (is_array($where)) {
			$this->parts = $where;
		} elseif ($where) {
			$this->add($where);
		}
//		$this->db = Config::getInstance()->getDB();
	}

	public function injectDB(DBInterface $db)
	{
		//debug(__METHOD__, gettype2($db));
		$this->db = $db;
		foreach ($this->parts as $p) {
			if ($p instanceof SQLWherePart) {
				$p->injectDB($this->db);
			}
		}
	}

	public function add($where, $key = null)
	{
		if (is_array($where)) {
			//debug($where);
			throw new InvalidArgumentException(__METHOD__);
		}
		if (!$key || is_numeric($key)) {
			$this->parts[] = $where;
		} else {
			$this->parts[$key] = $where;
		}
	}

	public function addArray(array $where)
	{
		foreach ($where as $key => $el) {
			$this->add($el, $key);
		}
		return $this;
	}

	public function __toString()
	{
		if ($this->parts) {
//			debug($this->parts);
			$strings = [];
			foreach ($this->parts as $field => $p) {
				if ($p instanceof SQLWherePart) {
					$p->injectDB($this->db);
					if (!is_numeric($field)) {
						$p->injectField($field);
					}
				} else {
					/*					$where = $this->db->quoteWhere(array(
											$field => $p,
										));
										$p = first($where);
					*/
					$p = new SQLWhereEqual($field, $p);
					$p->injectDB($this->db);
				}
				$strings[] = $p->__toString();
			}
			$sWhere = " WHERE\n\t" . implode("\n\tAND ", $strings);    // __toString()

			return $this->replaceParams($sWhere);
		}

		return '';
	}

	public function replaceParams($sWhere)
	{
		// replace $0$, $0$, $0$ with $1, $2, $3
		$params = $this->getParameters();
		//debug($sWhere, $params);
		foreach ($params as $i => $name) {
			if ($this->db->isMySQL()) {
				$sWhere = str_replace_once('$0$', '?', $sWhere);
			} else {
				$sWhere = str_replace_once('$0$', '$' . ($i + 1), $sWhere);
			}
		}
		return $sWhere;
	}

	/**
	 * @return array
	 */
	public function getAsArray()
	{
		return $this->parts;
	}

	public function debug()
	{
		return $this->parts;
	}

	public static function genFromArray(array $where)
	{
		foreach ($where as $key => &$val) {
			if (!($val instanceof SQLWherePart)) {
				$val = new SQLWhereEqual($key, $val);
			}
		}
		return new self($where);
	}

	public function getParameters()
	{
		$parameters = [];
		foreach ($this->parts as $part) {
			if ($part instanceof SQLWherePart) {
				$plus = $part->getParameter();
				//debug(typ($part), $part->getField(), $plus);
				if (is_array($plus)) {
					$parameters = array_merge($parameters, $plus);
				} elseif (!is_null($plus)) {
					// add even if empty string or 0
					$parameters[] = $plus;
				}
			}
		}
//		debug($parameters);
		return $parameters;
	}

	public function offsetExists(mixed $offset): bool
	{
		return isset($this->parts[$offset]);
	}

	public function offsetGet(mixed $offset): mixed
	{
		return $this->parts[$offset];
	}

	public function offsetSet(mixed $offset, mixed $value): void
	{
		$this->parts[$offset] = $value;
	}

	public function offsetUnset(mixed $offset): void
	{
		unset($this->parts[$offset]);
	}
}
