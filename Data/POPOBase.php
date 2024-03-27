<?php

/**
 * Class POPOBase - extends this class and add some properties
 * Then you can instantiate an object of your class and provide some
 * JSON data, it will extract and convert JSON data to POPO
 */
class POPOBase
{

	/**
	 * @var ReflectionClass
	 */
	protected $reflector;

	public $_missingProperties = [];

	public function __construct($set)
	{
		if (is_object($set)) {
			foreach (get_object_vars($set) as $key => $val) {
				$this->$key = $this->transform($key, $val);
			}
		} elseif (is_array($set)) {
			foreach ($set as $key => $val) {
				$this->$key = $this->transform($key, $val);
			}
		}
	}

	public function transform($name, $value)
	{
		$reflector = new ReflectionClass($this);
		try {
			$prop = $reflector->getProperty($name);
			if ($prop) {
				$type = $prop->getType() ? $prop->getType()->getName() : null;
				if (!$type) {
					$docText = $prop->getDocComment();
					$doc = new DocCommentParser($docText);
					$type = $doc->getFirstTagValue('var');
//					llog($docText, $type, $value);
				}
//				llog($name, $type.'', $value);
				switch ($type) {
					case 'integer':
					case 'int':
						$value = (int)$value;
						break;
					case 'string':
						$value = (string)($value);
						break;
					case 'boolean':
					case 'bool':
						$value = (bool)$value;
						break;
					case 'float':
						$value = (float)$value;
						break;
					case 'DateTime':
					case \DateTime::class:
						if (is_object($value)) {
							$value = new DateTime($value->date);
						} elseif ($value) {
							$value = new DateTime($value);
						}
						break;
					case 'DateTimeImmutable':
						if (is_object($value)) {
							$value = new DateTimeImmutable($value->date);
						} elseif ($value) {
							$value = new DateTimeImmutable($value);
						}
						break;
					default:
						// inner subclasses
						if ($type && class_exists($type)) {
							$value = new $type($value);
						}
				}
			}
		} catch (ReflectionException $e) {
			$this->_missingProperties[$name] = TAB . 'public $' . $name . ';';
		}
		return $value;
	}

	/**
	 * Only public properties will be included
	 * @return false|string
	 * @throws JsonException
	 */
	public function toJson()
	{
		return json_encode($this, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
	}

	public function __sleep()
	{
		$varNames = get_object_vars($this);
		$varNames = array_merge($varNames, $varNames);
		unset($varNames['reflector']);
		$varNames = array_keys($varNames);
//		llog($varNames);
		return $varNames;
	}

}
