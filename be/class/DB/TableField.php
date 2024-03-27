<?php

class TableField
{

	public $field;

	public $type;

	public $collation;

	public $isNull;

	public $key;

	public $default;

	public $comment;

	public $extra = [];

	public $references;

	public static function init(array $row)
	{
		//debug($row); exit();
		if (isset($row['cid']) || isset($row['pk'])) {
			$self = self::initSQLite($row);
		} elseif (isset($row['Field'])) {
			$self = self::initMySQL($row);
		} elseif (isset($row['array dims'])) {
			$self = self::initPostgreSQL($row);
		} else {
			throw new Exception(__METHOD__ . ' Unable to identify DB type');
		}
		return $self;
	}

	/**
	 * Field    string[2]    id
	 * Type    string[7]    int(11)
	 * Null    string[2]    NO
	 * Key    string[3]    PRI
	 * Default    NULL
	 * Extra    string[14]    auto_increment
	 * @param array $row
	 * @return TableField
	 */
	public static function initMySQL(array $row)
	{
		$self = new self();
		$self->field = $row['Field'];
		$self->type = $row['Type'];
		$self->isNull = $row['Null'] == 'YES';
		$self->key = $row['Key'];
		$self->default = $row['Default'];
		$self->extra = trimExplode(' ', $row['Extra']);
		return $self;
	}

	/**
	 * cid    string[1]    0
	 * name    string[2]    id
	 * type    string[7]    integer
	 * notnull    string[1]    1
	 * dflt_value    NULL
	 * pk    string[1]    1
	 * Field    string[2]    id
	 * Type    string[7]    integer
	 * Null    string[2]    NO
	 * @param array $desc
	 * @return TableField
	 */
	public static function initSQLite(array $desc)
	{
		$self = new self();
		$self->field = $desc['name'];
		$self->type = $desc['type'];
		$self->isNull = $desc['notnull'] ? false : true;
		$self->default = self::unQuote($desc['dflt_value']);
		$self->key = $desc['pk'] ? 'PRIMARY_KEY' : '';
		//debug($desc, $self); exit();
		return $self;
	}

	/**
	 * array(8) {
	 * 'num'  =>  int(15)
	 * 'type'  =>  string(4) "int4"
	 * 'len'  =>  int(4)
	 * 'not null'  =>  bool(false)
	 * 'has default'  =>  bool(false)
	 * 'array dims'  =>  int(0)
	 * 'is enum'  =>  bool(false)
	 * 'pg_field'  =>  string(12) "id_publisher"
	 * @param array $desc
	 * @return TableField
	 */
	public static function initPostgreSQL(array $desc)
	{
		$self = new self();
		$self->field = $desc['pg_field'];
		$self->type = $desc['type'];
		$self->isNull = !$desc['not null'];
		$self->default = $desc['has default'] ? null : null;
		$self->extra = $desc;
		return $self;
	}

	public static function unQuote($string)
	{
		$first = $string[0];
		if ($first == '"' || $first == "'") {
			$string = str_replace($first, '', $string);
		}
		return $string;
	}

	public function __toString()
	{
		$copy = get_object_vars($this);
		$copy['isNull'] = $copy['isNull'] ? 'is NULL' : 'NOT NULL';
		$copy['default'] = $copy['default'] ? 'DEFAULT [' . $copy['default'] . ']' : '';
		$copy['extra'] = implode(' ', $copy['extra']);
		return implode(' ', $copy);
	}

	public function isBoolean()
	{
		return in_array($this->type, ['bool', 'boolean', 'binary(1)']);
	}

	public function isNull()
	{
		return $this->isNull;
	}

	public function isInt()
	{
		return in_array($this->type, [
			'int', 'integer', 'INTEGER',
			'int(4)', 'int4', 'int(11)',
			'tinyint(1)', 'tinyint(4)']);
	}

	public function isText()
	{
		return in_array($this->type, ['text', 'varchar(255)', 'tinytext', 'string']);
	}

	public function isTime()
	{
		return in_array($this->type, ['numeric', 'timestamp', 'datetime']);
	}

	public function isFloat()
	{
		return in_array($this->type, ['real', 'double', 'float']);
	}

	public function fromPHP($type)
	{
		$map = [
			'string' => 'varchar',
			'int' => 'int',
			'float' => 'float',
		];
		return ifsetor($map[$type], $type);
	}

}
