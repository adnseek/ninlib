<?php

use Psr\Log\LoggerInterface;

/**
 * Base class for storing datasets or datarows or tabular data or set
 * or array of OODBase based objects.
 *
 */
/*abstract*/ // commented because of createForTable()
class Collection implements IteratorAggregate, ToStringable
{

	/**
	 * @var string
	 */
	public $table = __CLASS__;
	public $idField = 'uid';
	public $parentID = null;
	public $thes = [];
	public $titleColumn = 'title';

	/**
	 * Basic where SQL params to be included in every SQL by default
	 * @var array
	 */
	public $where = [];

	/**
	 * for LEFT OUTER JOIN queries
	 * @var string
	 */
	public $join = '';

	/**
	 * Initialize in postInit() to run paged SQL
	 * initialize if necessary with = new Pager(); in postInit()
	 * @var Pager|null
	 */
	public $pager;
	/**
	 * @var PageSize
	 */
	public $pageSize;
	/**
	 * objectify() without parameters will try this class name
	 * Default is NULL in order to check whether it's set or not.
	 * @var string
	 */
	public $itemClassName;
	/**
	 * SQL part
	 * @var string
	 */
	public $orderBy = "ORDER BY id";
	/**
	 * getQuery() stores the final query here for debug
	 * is null until initialized
	 * @var string|null
	 */
	public $query;
	/**
	 * Is NULL until it's set to 0 or more
	 * @var int Total amount of data retrieved (not limited by Pager)
	 */
	public $count = null;
	/**
	 * Lists columns for the SQL query
	 * @var string
	 * @default "DISTINCT table.*"
	 */
	public $select;
	public $doCache = true;
	/**
	 * @var array
	 */
	public $log = [];
	/**
	 * HTMLFormTable
	 * @var array
	 */
	public $desc = [];
	/**
	 * @var callable
	 */
	public $prepareRenderRow;
	/**
	 * Gives warnings if 'id' column in the data is not set.
	 * Potentially saves you from trouble futher down the processing.
	 * @var bool
	 */
	public $allowMerge = false;
	public $objectifyByInstance = false;
	/**
	 * In case of MSSQL it needs to be set from outside
	 * @var DBInterface
	 * @protected because it's visible in debug
	 * use injection if you need to modify it
	 */
	protected $db;
	protected $parentField = 'pid';
	/**
	 * Retrieved rows from DB
	 * Protected in order to force usage of getData()
	 * @var ArrayPlus|null
	 * @note should not be |array because it's used as ArrayPlus
	 */
	protected $data;
	/**
	 * objectify() stores objects generated from $this->data here
	 * array of objects converted from $this->data // convert to public
	 * @var array
	 */
	protected $members = [];
	/**
	 * Should it be here? Belongs to the controller?
	 * @var Request
	 */
	protected $request;
	/**
	 * @var Controller
	 */
	protected $controller;
	/**
	 * @var CollectionView
	 */
	protected $view;
	/**
	 * @var bool - if preprocessData() is called
	 */
	protected $processed = false;
	/**
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * @param int /-1 $pid
	 *        if -1 - will not retrieve data from DB
	 *        if 00 - will retrieve all data
	 *        if >0 - will retrieve data where PID = $pid
	 * @param array|SQLWhere $where
	 * @param string $order - appended to the SQL
	 * @param DBInterface $db
	 * @throws Exception
	 */
	public function __construct(
		$pid = null, /*array/SQLWhere*/
		$where = [], $order = '', DBInterface $db = null
	) {
		//$taylorKey = get_class($this).'::'.__FUNCTION__." ({$this->table})";
		$taylorKey = Debug::getBackLog(5, 0, BR, false);
		TaylorProfiler::start($taylorKey);
		$config = Config::getInstance();
		$this->db = $db ?: $config->getDB();
		$this->table = $config->prefixTable($this->table);

		if (!$this->select) {
			$firstWordFromTable = $this->db->getFirstWord($this->table);
//			$firstWordFromTable2 = SQLBuilder::getFirstWord($this->table);
//			debug($this->table, $firstWordFromTable, $firstWordFromTable2, typ($this->db));
			$this->select =
				// DISTINCT is 100 times slower, add it manually if needed
				//?: 'DISTINCT /*auto*/ '.$this->db->getFirstWord($this->table).'.*';
				$this->db->quoteKey($firstWordFromTable) . '.*';
		}
		$this->parentID = $pid;

		if (is_array($where)) {
			// array_merge should be use instead of array union,
			// in order to prevent existing entries with numeric keys being ignored in $where
			// simple array_merge will reorder numeric keys which is not good
			$this->where = ArrayPlus::create($this->where)->merge_recursive_overwrite($where)->getData();
		} elseif ($where instanceof SQLWhere) {
			$this->where = $where->addArray($this->where);
		}

		//debug($this->where);
		$this->orderBy = $order ? $order : $this->orderBy;
		$this->request = Request::getInstance();
		$this->postInit();

		foreach ($this->thes as &$val) {
			$val = is_array($val) ? $val : ['name' => $val];
		}
		$this->translateThes();

		if (!empty($this->parentID)) {    // > 0 will fail on string ID
			if ($this->parentID instanceof Date) {
				$this->where[$this->parentField] = $this->parentID->getMySQL();
			} elseif ($this->parentID instanceof OODBase) {
				$this->where[$this->parentField] = $this->parentID->id;
			} else {
				$this->where[$this->parentField] = is_array($this->parentID)
					? new SQLIn($this->parentID)
					: $this->parentID;
			}
		}

		TaylorProfiler::stop($taylorKey);
	}

	public function postInit()
	{
		//$this->pager = new Pager();
		if (class_exists('Index', false)) {
			$index = Index::getInstance();
			$this->controller = &$index->controller;
		}
		//debug(get_class($this->controller));
	}

	public function log($action, $data = [])
	{
//		llog($action, $data);
		if ($this->logger) {
			if (!is_array($data)) {
				$data = ['data' => $data];
			}
			$this->logger->info($action, $data);
		} else {
			$this->log[] = new LogEntry($action, $data);
		}
	}

	public function isFetched()
	{
		return $this->query && $this->data !== null;
		// we may have fetched only 0 rows
		//|| !$this->data->count())) {
	}

	/**
	 * @param bool $preProcess
	 * @return ArrayPlus
	 * @throws DatabaseException
	 * @throws MustBeStringException
	 */
	public function getData($preProcess = true)
	{
		$this->log(get_class($this) . '::' . __FUNCTION__ . '(preProcess=' . $preProcess . ')');
		$this->log(__METHOD__, 'collection Where' . json_encode($this->where));
		$this->log(__METHOD__, 'query: ' . $this->query . '');
		$this->log(__METHOD__, [
			'data' => $this->data
				? count($this->data)
				: '-',
			'data->count' =>
				$this->data === null ? 'NULL' : count($this->data),
			'isFetched' => $this->isFetched(),
		]);
		if (!$this->isFetched()) {
			$this->retrieveData();
			if ($preProcess) {
				$this->preprocessData();
				$this->log(__METHOD__, 'rows: ' . count($this->data));
			}
		}
		// although this is ugly - SoftwareGrid still needs that
		if (!($this->data instanceof ArrayPlus)) {
			$this->data = ArrayPlus::create($this->data);

			// PROBLEM! This is supposed to be the total amount
			// Don't uncomment
			//$this->count = sizeof($this->data);
		}
		return $this->data;
	}

	/**
	 * A function to fake the data as if it was retrieved from DB
	 * @param $data array|ArrayPlus
	 */
	public function setData($data)
	{
		$this->log(get_class($this) . '::' . __FUNCTION__ . '(' . count($data) . ')');
		$this->log(__METHOD__, ['from' => Debug::getCaller(2)]);
		//debug_pre_print_backtrace();
		//$this->log(__METHOD__, get_call_stack());
		if ($data instanceof ArrayPlus) {
			$this->data = $data;    // preserve sorting
		} else {
			$this->data = ArrayPlus::create((array)$data);
		}
		// PROBLEM! This is supposed to be the total amount
		// Don't uncomment
		//$this->count = count($this->data);
		$this->count = __METHOD__;    // we need to disable getCount()

		// this is needed to not retrieve the data again
		// after it was set (see $this->getData()
		// which is called in $this->render())
		$this->query = $this->query ?: __METHOD__;
	}

	/**
	 * id => -1 will prevent data retrieval
	 * @throws DatabaseException
	 * @throws MustBeStringException
	 * @throws Exception
	 */
	public function retrieveData()
	{
		$this->log(get_class($this) . '::' . __FUNCTION__, [
			'allowMerge' => $this->allowMerge,
		]);
		$this->log(__METHOD__, 'collection Where' . json_encode($this->where));
		$cq = $this->getCollectionQuery();
		$data = $cq->retrieveData();
		$this->log = array_merge($this->log, $cq->log);

		$this->log(__METHOD__, 'rows: ' . count($data));
		$this->log(__METHOD__, 'idealize by ' . $this->idField);
//		debug_pre_print_backtrace();
//		debug(get_class($this->db), $isMySQL, $this->query, $data, $this->log);
		$this->data = ArrayPlus::create($data);
		//$this->log(__METHOD__, $this->data->pluck('id'));
		$this->data->IDalize($this->idField, $this->allowMerge);
		$this->log(__METHOD__, 'rows: ' . count($this->data));
	}

	/**
	 * @return CollectionQuery
	 */
	public function getCollectionQuery(): CollectionQuery
	{
		static $cq = [];
		$hash = implode(':', [
			spl_object_hash($this),
			spl_object_hash($this->db),
			sha1(json_encode($this->table)),
			sha1(json_encode($this->join)),
			sha1(json_encode($this->where)),
			sha1(json_encode($this->orderBy)),
			sha1(json_encode($this->select)),
			$this->pager ? spl_object_hash($this->pager) : '',
		]);
		$this->log(__METHOD__, substr(sha1($hash), 0, 6) . json_encode($this->where));
		if (!ifsetor($cq[$hash])) {
			$cq[$hash] = new CollectionQuery(
				$this->db,
				$this->table,
				$this->join,
				$this->where,
				$this->orderBy,
				$this->select,
				$this->pager
			);
		}
		return $cq[$hash];
	}

	/**
	 * Make sure to retrieveData() before
	 * We can't fetchData here as it will make an infinite loop
	 * @return ArrayPlus|null
	 * @throws DatabaseException
	 * @throws MustBeStringException
	 */
	public function preprocessData()
	{
		$profiler = get_class($this) . '::' . __FUNCTION__ . " ({$this->table}): " . $this->getCount();
		TaylorProfiler::start($profiler);
		$this->log(get_class($this) . '::' . __FUNCTION__ . '()');
		if (!$this->processed) {
			$count = $this->getCount();
			// Iterator by reference
			$data = $this->getData()->getArrayCopy();
			foreach ($data as $i => &$row) {
				$row = $this->preprocessRow($row);
			}
			$this->data->setData($data);
			$this->log(__METHOD__, 'rows: ' . count($this->data));
			$this->processed = true;
			$this->count = $count;
		}
		$this->log(get_class($this) . '::' . __FUNCTION__ . '() done');
		TaylorProfiler::stop($profiler);
		return $this->data;    // return something else if you augment $this->data
	}

	/**
	 * Don't update $this->query otherwise getData() will think we have
	 * retrieved nothing.
	 * Count can be a heavy operation, we should only query the count once.
	 * But if the query changes, the count needs to be updated.
	 * @return int
	 * @throws DatabaseException
	 * @throws MustBeStringException
	 */
	public function getCount()
	{
		$this->log(get_class($this) . '::' . __FUNCTION__, ['original', $this->count]);
//		$this->log('this->query', $this->query.'');
//		$this->log('getQueryWithLimit', $this->getQueryWithLimit().'');
		$queryIsTheSame = ($this->query . '') === ($this->getQueryWithLimit() . '');
		if ($this->count !== null && $queryIsTheSame) {
			return intval($this->count);
		}
		$this->query = $this->getQueryWithLimit();     // will init pager
		if ($this->pager && $this->pager->numberOfRecords) {
			$this->count = $this->pager->numberOfRecords;
		} else {
			$cq = $this->getCollectionQuery();
			$counter = new SQLCountQuery($cq);
			$this->count = $counter->getCount();
		}
		$this->log(get_class($this) . '::' . __FUNCTION__, ['exit', $this->count]);
		return intval($this->count);
	}

	public function getQueryWithLimit()
	{
		$cq = $this->getCollectionQuery();
		return $cq->getQueryWithLimit();
	}

	/**
	 * Override me to make changes
	 * @param array $row
	 * @return array
	 */
	public function preprocessRow(array $row)
	{
		return $row;
	}

	public function translateThes()
	{
		if (is_array($this->thes)) {
			foreach ($this->thes as &$trans) {
				if (is_string($trans) && $trans) {
					$trans = __($trans);
				}
			}
		}
		//debug_pre_print_backtrace();
	}

	/**
	 * @param DBInterface $db
	 * @param string $table
	 * @param array $where
	 * @param string $orderBy
	 * @return Collection
	 * @return string[] - returns the slTable if not using Pager
	 * @throws Exception
	 */
	public static function createForTable(DBInterface $db, $table, array $where = [], $orderBy = '')
	{
		$c = new self();
		$c->db = $db;
		$c->table = $table;
		$c->where = $where;
		$c->orderBy = $orderBy;
		/** @var DBLayerBase $db */
		//$db = Config::getInstance()->getDB();
		$firstWord = SQLBuilder::getFirstWord($c->table);
		$firstWord = $db->quoteKey($firstWord);
		$c->select = ' ' . $firstWord . '.*';
		assert($db === $c->db);
		return $c;
	}

	public static function hydrate($source)
	{
		$class = $source->class;
		/** @var Collection $object */
		$object = new $class();
		$object->count = $source->count;
		$memberClass = $object->itemClassName;
		foreach ($source->members as $id => $m) {
			$child = new $memberClass();
			$child->id = $id;
			$child->data = (array)$m->data;
			$object->members[$id] = $child;
		}
		return $object;
	}

	/**
	 * @return array
	 */
	public function getOptions()
	{
		$options = [];
		//debug(get_class($this), $this->table, $this->titleColumn, $this->getCount());
		foreach ($this->getProcessedData() as $row) {
			//if ( !in_array($row[$this->idField], $blackList) ) {
			$options[$row[$this->idField]] = $row[$this->titleColumn];
			//}
		}
		return $options;
	}

	public function getProcessedData()
	{
		if ($this->processed) {
			return $this->data;
		}

		if ($this->data) {
			$this->preprocessData();
			return $this->data;
		}

		$this->getData();
		$this->preprocessData();
		return $this->data;
	}

	public function prepareRenderRow(array $row)
	{
		if (is_callable($this->prepareRenderRow)) {
			$closure = $this->prepareRenderRow;
			$row = $closure($row);
		}
		return $row;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getLinks()
	{
		$options = [];
		//debug(get_class($this), $this->table, $this->titleColumn, $this->getCount());
		//debug($this->itemClassName, $this->idField, $this->titleColumn, sizeof($this->members), first($this->getData()->getData()));
		foreach ($this->objectify() as $obj) {
			//debug($obj->id, $obj->getName());
			$options[$obj->id] = $obj->getNameLink();
		}
		return $options;
	}

	/**
	 * Will detect double-call and do nothing.
	 *
	 * @param string $class - required, but is supplied by the subclasses
	 * @param bool $byInstance - will call getInstance() instead of "new"
	 * @return object[]|OODBase[]
	 * @throws Exception
	 */
	public function objectify($class = null, $byInstance = false)
	{
		$class = $class ?: $this->itemClassName;
		if ($this->members) {
			return $this->members;
		}

		$this->log(__METHOD__, ['class' => $class, 'instance' => $byInstance]);
		$this->members = [];   // somehow necessary
		foreach ($this->getData() as $row) {
			$key = $row[$this->idField];
			if ($byInstance) {
				//$this->members[$key] = call_user_func_array(array($class, 'getInstance'), array($row));
				$this->members[$key] = call_user_func($class . '::getInstance', $row);
			} else {
				$this->members[$key] = new $class($row);
			}
		}
		return $this->members;
	}

	public function getObjects()
	{
		return $this->objectify();
		// what to composer request for this?
//		return collect($this->objectify());
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
	 * @param array $where
	 * @return array - of matching rows
	 * @throws Exception
	 */
	public function findAllInData(array $where)
	{
		$result = [];
		foreach ($this->getData() as $row) {
			$intersect1 = array_intersect_key($row, $where);
			$intersect2 = array_intersect_key($where, $row);
			if ($intersect1 == $intersect2) {
				$result[] = $row;
			}
		}
		return $result;
	}

	public function renderList()
	{
		$list = [];
		if ($this->getCount()) {
			foreach ($this->getProcessedData() as $id => $row) {
				if ($this->itemClassName) {
					$list[$id] = $this->renderListItem($row);
				} elseif ($this->thes) {
					$row = $this->prepareRenderRow($row);   // add link
					$item = '';
					foreach ($this->thes as $key => $_) {
						$item .= $row[$key] . ' ';
					}
					$list[$id] = $item;
				} else {
					$list[$id] = $row[$this->titleColumn];
				}
			}
			return new UL($list);
		}
		return null;
	}

	public function renderListItem(array $row)
	{
		/** @var OODBase $obj */
		$class = $this->itemClassName;
		if (method_exists($class, 'getInstance')) {
			$obj = $class::getInstance($row);
		} else {
			$obj = new $class($row);
		}

		if (method_exists($obj, 'render')) {
			$content = $obj->render();
		} elseif (method_exists($obj, 'getSingleLink')) {
			$link = $obj->getSingleLink();
			if ($link) {
				$content = new HTMLTag('a', [
					'href' => $link,
				], $obj->getName());
			} else {
				$content = $obj->getName();
			}
		} else {
			$content = $obj->getName();
		}
		return $content;
	}

	/**
	 * @return string - returns the slTable if not using Pager
	 * @throws Exception
	 */
	public function render()
	{
		$view = $this->getView();
		return $view->renderTable();
	}

	public function getView()
	{
		if (!$this->view) {
			$this->view = new CollectionView($this);
		}
		return $this->view;
	}

	public function setView($view)
	{
		$this->view = $view;
		return $this;
	}

	/**
	 * Calls __toString on each member
	 * @return string
	 */
	public function renderMembers()
	{
		return $this->getView()->renderMembers();
	}

	public function objectifyAsPlus()
	{
		return ArrayPlus::create(
			$this->objectify($this->itemClassName, $this->objectifyByInstance)
		);
	}

	public function __toString()
	{
		return $this->render() . '';
	}

	/**
	 * Wrap output in <form> manually if necessary
	 * @param string $idFieldName Optional param to define a different ID field to use as checkbox value
	 * @throws Exception
	 */
	public function addCheckboxes($idFieldName = '')
	{
		$this->log(get_class($this) . '::' . __FUNCTION__);
		$this->thes = ['checked' => [
				'name' => '<a href="javascript:void(0)">
					<input type="checkbox"
					id="checkAllAuto"
					name="checkAllAuto"
					onclick="checkAll(this)" /></a>', // if we need sorting here just add ""
				'align' => "center",
				'no_hsc' => true,
			]] + $this->thes;
		$class = get_class($this);
		$data = $this->getData();
		$count = $this->getCount();
		foreach ($data as &$row) {
			$id = !empty($idFieldName) ? $row[$idFieldName] : $row[$this->idField];
			$checked = ifsetor($_SESSION[$class][$id])
				? 'checked="checked"' : '';
			$row['checked'] = '
			<input type="checkbox" name="' . $class . '[' . $id . ']"
			value="' . $id . '" ' . $checked . ' />';
		} // <form method="POST">
		$this->setData($data);
		$this->count = $count;
		$this->log(get_class($this) . '::' . __FUNCTION__ . ' done');
	}

	/**
	 * Uses array_merge to prevent duplicates
	 * @param Collection $c2
	 * @throws Exception
	 */
	public function mergeData(Collection $c2)
	{
		$before = array_keys($this->getData()->getData());
		//$this->data = array_merge($this->data, $c2->data);	// don't preserve keys
		$myObjects = $this->objectify();
		$data2 = $c2->getData()->getData();
		$this->data = $this->getData()->merge_recursive_overwrite($data2);
		$this->members = $myObjects + $c2->objectify();
		$this->count += $c2->count;
		//debug($before, array_keys($c2->data), array_keys($this->data));
	}

	public function getObjectInfo()
	{
		$list = [];
		foreach ($this->members as $obj) {
			/** @var $obj OODBase */
			$list[] = $obj->getObjectInfo();
		}
		return $list;
	}

	public function getLazyIterator()
	{
		$query = $this->getCollectionQuery()->getQuery();
		//debug($query);

		$lazy = new DatabaseResultIteratorAssoc($this->db, $this->idField);
		$lazy->perform($query);
		$this->query = $lazy->query;

		return $lazy;
	}

	/**
	 * @param string $class
	 * @return LazyMemberIterator|$class[]
	 */
	public function getLazyMemberIterator($class = null)
	{
		if (!$class) {
			$class = $this->itemClassName;
		}
		$arrayIterator = $this->getLazyIterator();
		$memberIterator = new LazyMemberIterator($arrayIterator, $class);
		$memberIterator->count = $arrayIterator->count();
		return $memberIterator;
	}

	public function clearInstances()
	{
		unset($this->data);
		unset($this->members);
	}

	public function getJson()
	{
		$members = [];
		foreach ($this->objectify() as $id => $member) {
			$members[$id] = $member->getJson();
		}
		return [
			'class' => get_class($this),
			'count' => $this->getCount(),
			'members' => $members,
		];
	}

	public function reload()
	{
		$this->reset();
		$this->retrieveData();
	}

	/**
	 * Make sure we re-query data from DB
	 */
	public function reset()
	{
		$this->count = null;
		$this->query = null;
		$this->data = null;
		$this->members = null;
	}

	/**
	 * @param object $obj
	 * @return bool
	 * @throws Exception
	 */
	public function contains($obj)
	{
		foreach ($this->objectify() as $mem) {
			if ($mem == $obj) {
				return true;
			}
		}
		return false;
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Retrieve an external iterator
	 * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
	 * @return ArrayPlus Traversable An instance of an object implementing <b>Iterator</b> or
	 * <b>Traversable</b>
	 * @throws Exception
	 */
	public function getIterator(): Traversable
	{
		return new ArrayPlus($this->objectify($this->itemClassName, $this->objectifyByInstance));
	}

	public function get($id)
	{
		$members = $this->objectify();
		return ifsetor($members[$id]);
	}

	public function setMembers(array $countries)
	{
		$this->members = $countries;
		$this->count = count($this->members);
		$this->query = __METHOD__;

		$this->data = ArrayPlus::create();
		foreach ($this->members as $obj) {
			$this->data[$obj->id] = $obj->data;
		}
	}

	public function first()
	{
		return first($this->objectify());
	}

	public function containsID($id)
	{
		return in_array($id, $this->getIDs());
	}

	public function getIDs()
	{
		return $this->getData()->getKeys()->getData();
	}

	public function containsName($name)
	{
		foreach ($this->getData() as $row) {
			if ($row[$this->titleColumn] === $name) {
				return true;
			}
		}
		return false;
	}

	public function setDB(DBInterface $ms)
	{
		$this->db = $ms;
	}

	public function unobjectify()
	{
		foreach ($this->objectify() as $i => $el) {
			$this->data[$i] = $el->data;
		}
	}

	public function getLogger()
	{
		return $this->logger;
	}

	public function setLogger($log)
	{
		$this->logger = $log;
	}

}
