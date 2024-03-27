<?php

/**
 * Class FlexiTable extends OODBase allowing to automatically create new tables
 * and add new DB columns based on INSERT and UPDATE queries. Useful for quick DB prototyping.
 * Data type for new columns is not perfect.
 */
class FlexiTable extends OODBase
{

	/**
	 * @var array
	 */
	protected $columns = [];

	/**
	 * Enables/disables FlexiTable functionality
	 * @var bool
	 */
	public $doCheck = false;

	/**
	 * array(
	 *        $table => array('id' => ...)
	 * )
	 *
	 * @var array
	 */
	protected static $tableColumns = [];

	/**
	 * @var string 'ctime'
	 */
	public $ctimeField;

	/**
	 * @var string 'cuser'
	 */
	public $cuserField;

	/**
	 * @var string 'mtime'
	 */
	public $mtimeField;

	/**
	 * @var string 'muser'
	 */
	public $muserField;

	public function __construct($id = null)
	{
		parent::__construct($id);
		$config = ifsetor(Config::getInstance()->config);
		if (is_array($config)) {
			//debug(ifsetor($config[__CLASS__]));
			if (isset($config[__CLASS__]['doCheck'])) {
				$this->doCheck = ifsetor($config[__CLASS__]['doCheck']);
				if ($this->doCheck) {
					$this->checkCreateTable();
				}
			}
		}
	}

	public function insert(array $row)
	{
		if ($this->ctimeField && !ifsetor($row[$this->ctimeField])) {
			$row[$this->ctimeField] = new SQLDateTime();
		}
		if ($this->cuserField && !ifsetor($row[$this->cuserField])) {
			$user = Config::getInstance()->getUser();
			$row[$this->cuserField] = ifsetor($user->id) ? $user->id : null;
		}
		if ($this->doCheck) {
			$this->checkAllFields($row);
		}
		$ret = parent::insert($row);
		return $ret;
	}

	public function update(array $row)
	{
		if ($this->mtimeField && !ifsetor($row[$this->mtimeField])) {
			$mtime = new Time();
			$row[$this->mtimeField] = $mtime->format('Y-m-d H:i:s');
		}
		$user = Config::getInstance()->getUser();
		if ($this->muserField
			&& !ifsetor($row[$this->muserField])
			&& is_object($user)
			&& $user->id) {
			$row[$this->muserField] = $user->id;
		}
		if ($this->doCheck) {
			$this->checkAllFields($row);
		}
//		$tempMtime = $this->data[$this->mtimeField];
		$res = parent::update($row);    // calls $this->init($id) to update data
		//debug($this->data['id'], $tempMtime, $row['mtime'], $this->data['mtime']);
		return $res;
	}

	public function findInDB(array $where, $orderBy = '', $selectPlus = null)
	{
		if ($this->doCheck) {
			$this->log(__METHOD__, 'Checking columns exist');
			$this->checkAllFields($where);
		}
		return parent::findInDB($where, $orderBy, $selectPlus);
	}

	public function checkAllFields(array $row)
	{
		$this->fetchColumns();
		foreach ($row as $field => $value) {
			$this->checkCreateField($field, $value);
		}
	}

	public function fetchColumns($force = false)
	{
		//TaylorProfiler::start(__METHOD__." ({$this->table}) <- ".Debug::getCaller(5));
		$table = str_replace('`', '', $this->table);
		$table = str_replace("'", '', $table);
		if (!ifsetor(self::$tableColumns[$table]) || $force) {
			self::$tableColumns[$table] = $this->db->getTableColumnsEx($table);
		}
		$this->columns = self::$tableColumns[$table];
		//debug($table, sizeof($this->columns), array_keys(self::$tableColumns), $this->db->lastQuery);
		//TaylorProfiler::stop(__METHOD__." ({$this->table}) <- ".Debug::getCaller(5));
	}

	public function checkCreateTable()
	{
		$this->fetchColumns();
		if (!$this->columns) {
			$query = 'CREATE TABLE ' . $this->db->escape($this->table) .
				' (id integer auto_increment, PRIMARY KEY (id))';
			$this->db->perform($query);
			$this->fetchColumns(true);
		}
	}

	public function checkCreateField($field, $value)
	{
		//debug($this->columns);
		$field = strtolower($field);
		$existingField = ifsetor($this->columns[$field]['Field']);
		if (strtolower($existingField) != $field) {
			$this->db->perform('ALTER TABLE ' . $this->db->escape($this->table) .
				' ADD COLUMN ' . $this->db->quoteKey($field) . ' ' . $this->getType($value));
			$this->fetchColumns(true);
		}
	}

	public function getType($value)
	{
		if (is_int($value)) {
			$type = 'integer';
		} elseif ($value instanceof Time) {
			$type = 'timestamp';
		} elseif (is_numeric($value)) {
			$type = 'float';
		} elseif ($value instanceof SimpleXMLElement) {
			$type = 'text';
		} else {
			$type = 'VARCHAR (255)';
		}
		return $type;
	}

	/**
	 * Can't store large amount of data in MySQL column
	 * Data may be either compressed - then we try to uncompress it
	 * Or it may be XML, then we convert it to the SimpleXML object
	 * Both operations take $this->data['field'] as a source
	 * and save the result into $this->$field
	 * @param bool $debug
	 */
	public function expand($debug = false)
	{
		static $stopDebug = false;
		$this->fetchColumns();
		foreach ($this->columns as $field => &$info) {
			if (in_array($info['Type'], ['blob', 'text']) && $this->data[$field]) {
				$info['uncompress'] = 'try';
				$uncompressed = $this->db->uncompress($this->data[$field]);
				if (!$uncompressed) {
					/*debug($info+array(
						'error' => $php_errormsg,
						'value' => $this->data[$field],
					)); exit();*/
					// didn't unzip - then it's plain text
					$uncompressed = $this->data[$field];
					$info['uncompress'] = 'Not necessary';
				} else {
					$info['uncompress'] = 'Uncompressed';
				}
				$this->data[$field] = $uncompressed;
				$info['first'] = $this->data[$field][0];
				if ($this->data[$field][0] === '<') {
					//$uncompressed = html_entity_decode($uncompressed, ENT_QUOTES, "utf-8");
					$this->$field = @simplexml_load_string($uncompressed);
					unset($this->data[$field]);
					$info['unxml'] = 'true';
				} elseif ($this->data[$field][0] === '{') {
					$this->$field = json_decode($uncompressed, false);    // make it look like SimpleXML
					unset($this->data[$field]);
					$info['unjson'] = 'true';
				}
			}
		}
		if ($debug && !$stopDebug) {
			debug($this->table, $this->columns);
			$stopDebug = true;
		}
		unset($this->data['xml']);
		unset($this->data['xml2']);
	}

}
