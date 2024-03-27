<?php

/**
 * Class Model - this is a replacement class for OODBase and Collection.
 * It's inconvenient to have two classes representing the same database table.
 * It's inconvenient to configure output of these two classes separately.
 * Control sorting and other things separately.
 * It's been written to represent a single source of truth for the whole model.
 * It was trying to use OODBase and Collection classes and convert to them.
 * But due to the missing Dependency Injection in these classed
 * we need to redesign the whole concept and deal with the data here.
 *
 * Principles:
 * - a Model represents a single entry
 * - a collection of Models of the same type are represented by ArrayPlus
 * - don't put collection methods directly into the Model - try to use ArrayPlus
 * - or add Traits that deal with multiple rows
 * @phpstan-consistent-constructor
 */
class Model
{

	public $table;

	public $idField = 'id';

	public $titleColumn = 'name';

	/**
	 * @var DBInterface|SQLBuilder|DBLayerBase
	 */
	protected $db;

	public $id;

	public $lastSelectQuery;
	public $lastInsertQuery;
	public $lastUpdateQuery;

	/**
	 * Not caching.
	 * @param array $data
	 * @param DBInterface $db
	 * @return static
	 * @throws DatabaseException
	 * @throws Exception
	 */
	public static function getInstance(array $data, DBInterface $db = null)
	{
		$obj = new static(null);
		$obj->setDB($db ?: Config::getInstance()->getDB());
		$obj->setData($data);
		return $obj;
	}

	/**
	 * Not caching.
	 * @param DBInterface $db
	 * @param $id
	 * @return static
	 */
	public static function getInstanceByID(DBInterface $db, $id)
	{
		$obj = new static($db, []);
		$obj->getByID($id);
		return $obj;
	}

	/**
	 * @param DBInterface|null $db
	 * @param array $data
	 */
	public function __construct(DBInterface $db = null, array $data = [])
	{
		if ($db) {
			$this->setDB($db);
		}
		$this->setData($data);
	}

	public function setDB(DBInterface $db)
	{
		$this->db = $db;
	}

	/**
	 * Different models may extend this to covert between
	 * different data types in DB and in runtime.
	 * @param array $data
	 */
	public function setData(array $data)
	{
		foreach ($data as $key => $val) {
			$this->$key = $val;
		}
	}

	public function unsetData()
	{
		foreach ($this->getFields() as $field => $dc) {
			$this->$field = null;
		}
	}

	public function getName()
	{
		$f = $this->titleColumn;
		return $this->$f;
	}

	/**
	 * @param array $where
	 * @return ArrayPlus
	 * @deprecated
	 */
	public function getData($where = [])
	{
		$data = $this->db->fetchAllSelectQuery($this->table, $where);
		$this->lastSelectQuery = $this->db->getLastQuery();
		if (!($data instanceof ArrayPlus)) {
			$data = new ArrayPlus($data);
		}
		return $data;
	}

	/**
	 * @param array $where
	 * @return ArrayPlus
	 * @deprecated
	 */
	public function query($where = [])
	{
		$data = $this->db->fetchAllSelectQuery($this->table, $where);
		$this->lastSelectQuery = $this->db->getLastQuery();
		if (!($data instanceof ArrayPlus)) {
			$data = new ArrayPlus($data);
		}
		$data->map(function ($row) {
			return new static($this->db, $row);
		});
		return $data;
	}

	public function renderList()
	{
		$list = [];
		if ($this->getData()->count()) {
			foreach ($this->getData() as $id => $row) {
				$this->setData($row);
				if (method_exists($this, 'render')) {
					$content = $this->render();
				} elseif (method_exists($this, 'getSingleLink')) {
					$link = $this->getSingleLink();
					if ($link) {
						$content = new HTMLTag('a', [
							'href' => $link,
						], $this->getName());
					} else {
						$content = $this->getName();
					}
				} else {
					$content = $this->getName();
				}
				$list[$id] = $content;
			}
			return new UL($list);
		}
		return null;
	}

	public function insert(array $data, array $where = [])
	{
		if (!isset($data[$this->idField])) {
			$data[$this->idField] = RandomStringGenerator::likeYouTube();
		}
		$res = $this->db->runInsertQuery($this->table, $data, $where);
		$this->lastInsertQuery = $this->db->getLastQuery();
		$this->setData($data);
		return $res;
	}

	/**
	 * Original runs getUpdateQuery() which is not supported
	 * by DBLayerJSON
	 * @param array $data
	 * @return resource
	 */
	public function update(array $data)
	{
		$res = $this->db->runUpdateQuery($this->table, $data, [
			$this->idField => $this->{$this->idField},
		]);
		if ($this->db->affectedRows($res) !== 1) {
			throw new DatabaseException($this->db->getLastQuery() . ' updated ' . $this->db->affectedRows($res) . ' rows');
		}
		$this->lastUpdateQuery = $this->db->getLastQuery();
		$this->setData($data);
		return $res;
	}

	public function getByID($id)
	{
		$found = $this->db->fetchOneSelectQuery($this->table, [
			$this->idField => $id,
		]);
		$this->lastSelectQuery = $this->db->getLastQuery();
		if ($found) {
			$this->setData($found);
		} else {
			$this->unsetData();
		}
		return $this;
	}

	public function getFormFromModel()
	{
		$desc = [];
		$fields = $this->getFields();
		foreach ($fields as $field => $dc) {
			$desc[$field] = [
				'label' => $dc->get('label')
					?: $dc->getDescription(),
				'type' => $dc->get('type') ?: 'text',
				// optional is true by default
				'optional' => $dc->is_set('optional') || !$dc->is_set('required'),
			];
		}
		return $desc;
	}

	/**
	 * @return DocCommentParser[]
	 */
	public function getFields()
	{
		$fields = [];
		foreach (get_object_vars($this) as $fieldName => $_) {
			try {
				$field = new ReflectionProperty(get_class($this), $fieldName);
				$sComment = $field->getDocComment();
				if ($sComment) {
					$dc = new DocCommentParser($sComment);
					//debug($field->getName(), $sComment, $dc->getAll());
					if ($dc->is_set('label') || $dc->is_set('field')) {
						$fields[$fieldName] = $dc;
					}
				}
			} catch (ReflectionException $e) {
				// skip
			}
		}
		return $fields;
	}

	function getVisibleFields()
	{
		// TODO
	}

	function id()
	{
		return $this->id;
	}

	function get($field)
	{
		return ifsetor($this->$field);
	}

	public function asArray()
	{
		$data = get_object_vars($this);
		unset($data['table']);
		unset($data['idField']);
		unset($data['titleColumn']);
		unset($data['db']);
		unset($data['lastSelectQuery']);
		unset($data['lastInsertQuery']);
		unset($data['lastUpdateQuery']);
		return $data;
	}

	public function getJSON()
	{
		return (object)$this->asArray();
	}

	public function getNameLink()
	{
		return HTMLTag::a($this->getSingleLink(), $this->getName());
	}

	public function getSingleLink()
	{
		return 'Controller?id=' . $this->id();
	}

	public function __toString()
	{
		return $this->getName();
	}

	/**
	 * CREATE TABLE x (...)
	 * @return mixed|string
	 * @throws AccessDeniedException
	 * @throws LoginException
	 * @throws ReflectionException
	 */
	public function createQuery()
	{
		$columns = [];
		$fields = $this->getFields();
		foreach ($fields as $field => $dc) {
//			debug($field);
			$f = new TableField();
			$f->field = $field;
			$f->comment = $dc->getDescription();
			$f->type = $f->fromPHP($dc->get('var')) ?: 'varchar';
			if (class_exists($f->type)) {
				$re = new ReflectionClass($f->type);
				$id = $re->getProperty('id');
				$dc2 = new DocCommentParser($id->getDocComment());

				$type = new $f->type();

				$f->type = $dc2->get('var')
					? first(trimExplode(' ', $dc2->get('var')))
					: 'varchar';
				$f->references = $type->table . '(' . $type->idField . ')';
			}
			$columns[] = $f;
		}
		$at = new AlterTable();
		$handler = $at->handler;
		return $handler->getCreateQuery($this->table, $columns);
	}

}
