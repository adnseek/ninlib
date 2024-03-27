<?php

class PersistantOODBase extends OODBase
{

	/**
	 * @var string
	 */
	protected $stateHash;

	/**
	 *
	 * @var array
	 */
	public $originalData;

	public static $inserted = 0;
	public static $updated = 0;
	public static $skipped = 0;

	// define them in a subclass for static::inserted to work

	public function __construct($initer)
	{
		parent::__construct($initer);
		$this->originalData = $this->data;
		$this->stateHash = $this->getStateHash();
		//debug($this->getStateHash(), $this->stateHash, $this->data, $this->id);
	}

	public function init($id)
	{
		parent::init($id);
	}

	public function getStateHash()
	{
		$isNull = array_reduce($this->data, function ($acc, $el) {
			return is_null($acc) && is_null($el) ? null : 'not null';
		}, null);
		//debug($this->data, $isNull); die;
		if (is_null($isNull)) {
			$this->data = [];
		}
		return md5(serialize($this->data));
	}

	public function __set($property, $value)
	{
		$this->data[$property] = $value;
	}

	public function __get($property)
	{
		if (isset($this->data[$property])) {
			return $this->data[$property];
		}
	}

	public function __destruct()
	{
		//debug(get_called_class());
		$this->save();
	}

	/**
	 * Insert updates state hash so that destruct will not try to insert again
	 *
	 * @param array $data
	 * @return OODBase
	 */
	public function insert(array $data)
	{
		$ret = null;
		nodebug([
			'insert before',
			$this->stateHash => $this->originalData,
			$this->getStateHash() => $this->data,
			$this->id,
		]);
		try {
			$ret = parent::insert($data);
		} catch (Exception $e) {
			//debug('LastInsertID() failed but it\'s OK');
			$ret = null;
		}
		//debug($this->db->lastQuery);
		$this->originalData = $this->data;
		$this->stateHash = $this->getStateHash();
		nodebug([
			'insert after',
			$this->stateHash => $this->originalData,
			$this->getStateHash() => $this->data,
			$this->id,
		]);
		return $ret;
	}

	/**
	 * Update updates state hash so that destruct will not try to update again
	 *
	 * @param array $data
	 * @return resource
	 * @throws Exception
	 */
	public function update(array $data)
	{
		$ret = parent::update($data);
		//debug($this->db->lastQuery);
		$this->originalData = $this->data;
		$this->stateHash = $this->getStateHash();
		return $ret;
	}

	public function isChanged()
	{
		return $this->getStateHash() != $this->stateHash;
	}

	public function isUpdate()
	{
		$idDefined = is_array($this->id)
			? trim(implode('', $this->id))
			: $this->id;
		return $idDefined;
	}

	public function save(array $where = null)
	{
		if ($this->isChanged()) {
			0 && debug([
				'stateHash' => $this->stateHash,
				'originalData' => $this->originalData,
				'getStateHash' => $this->getStateHash(),
				'data' => $this->data,
				'table' => $this->table,
				'id' => $this->id,
			]);
			if ($this->isUpdate()) {
				//debug(__CLASS__, $this->id, $this->getStateHash(), $this->stateHash, $this->data, $this->originalData);
				//debug(get_class($this), $this->id, $this->originalData, $this->data);
				$this->update($this->data);
				$action = 'UPDATE';
				static::$updated++;
			} else {
				$this->insert($this->data);
				$action = 'INSERT';
				static::$inserted++;
			}
		} else {
			$action = 'SKIP';
			static::$skipped++;
		}
		nodebug([
			$this->stateHash => $this->originalData,
			$this->getStateHash() => $this->data,
			$this->table => $this->id,
			'action' => $action,
		]);
		//debug('table: '.$this->table.' action: '.$action.' id: '.$this->id);
		return $action;
	}

	public function findInDB(array $where, $orderByLimit = '', $selectPlus = null)
	{
		$ret = parent::findInDB($where, $orderByLimit, $selectPlus);
		$this->originalData = $this->data;
		$this->stateHash = $this->getStateHash();
		return $ret;
	}

}
