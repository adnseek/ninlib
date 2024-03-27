<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Majid (Pedram) Jokar
 * Date: 01.10.13
 * Time: 16:52
 * To change this template use File | Settings | File Templates.
 */

class MessageQueue extends OODBase
{

	const STATUS_NEW = 'NEW';
	const STATUS_IN_PROGRESS = 'IN PROGRESS';
	const STATUS_DONE = 'DONE';
	const STATUS_FAILED = 'FAILED';

	public $table = 'message_queue';
	public $idField = 'id';

	/**
	 * Class name
	 * @var string
	 */
	private $type = null;

	/**
	 * Contains data
	 * @var array
	 */
	private $taskData = [];


	public function __construct($type)
	{
		parent::__construct();

		if (empty($type)) {
			throw new RuntimeException('Type not set!');
		}

		$this->type = $type;
	}

	/**
	 * TODO: move this into MessageQueueCollection
	 * Get next task available
	 * @return TaskInterface
	 * @throws Exception
	 */
	public function getTaskObject()
	{
		// need to delete previous record, otherwise infinite loop
		$this->id = null;
		$this->data = [];
		$this->db->transaction();
		$newTaskOK = $this->fetchNextTask($this->type);
		if ($newTaskOK) {
			// Set the status to "IN PROGRESS"
			$this->setStatus(self::STATUS_IN_PROGRESS);
			$this->db->commit();
			// set task data retrieved from DB
			$this->setTaskData($this->data['data']);

			try {
				$className = $this->getClassName($this->type);
				echo 'className: ', $className, BR;
				if (class_exists($className)) {
					$obj = new $className($this);
				} else {
					echo 'Class ' . $className . ' does not exist', BR;
					$obj = false;
				}
			} catch (Exception $e) {
				echo $e->getMessage(), BR;
				$obj = false;
			}
			return $obj;
		}

		$this->db->commit();    // tried to get new task

		// if there is no next task return false
		return null;
	}

	/**
	 * Fetches next task for given type from db
	 * and puts it's data into $this->data
	 *
	 * @param $type
	 * @return bool
	 */
	private function fetchNextTask($type)
	{
		$where = [
			'status' => self::STATUS_NEW,
			'type' => $type
		];

		$orderBy = 'ORDER BY id ASC';

		$this->findInDB($where, $orderBy);
		//debug($this->db->lastQuery, $this->data);

		if (!empty($this->data['id'])) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Sets status of current task
	 *
	 * @param string $status MessageQueue::STATUS_*
	 * @return void
	 */
	public function setStatus($status)
	{
		$data = [
			'status' => $status
		];
		$this->update($data);
	}

	public function update(array $data)
	{
		$data['mtime'] = new SQLNow();
		return parent::update($data);
	}

	/**
	 * Get class name for given type
	 *
	 * @param string $type
	 * @return string
	 */
	private function getClassName($type)
	{
		return $type;
	}

	public function count()
	{
		$where = [
			'status' => self::STATUS_NEW,
			'type' => $this->type,
		];
		$res = $this->db->runSelectQuery($this->table, $where);
		return $this->db->numRows($res);
	}

	/**
	 * Getter for $this->taskData
	 *
	 * @return array
	 */
	public function getTaskData()
	{
		return $this->taskData;
	}

	/**
	 * Setter for $this->taskData
	 *
	 * @param array $data
	 */
	public function setTaskData($data)
	{
		$this->taskData = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
	}

	/**
	 * Push a message into queue
	 *
	 * @param array $taskData
	 * @param int|null $userId If not provided current user is used
	 * @return OODBase
	 */
	public function push($taskData, $userId = null)
	{
		$data = [
			'ctime' => new SQLNow(),
			'type' => $this->type,
			'status' => self::STATUS_NEW,
			'data' => json_encode($taskData, JSON_THROW_ON_ERROR)
		];

		if (!empty($userId)) {
			$data['cuser'] = $userId;
		}

		return $this->insert($data);
	}

	public function getStatus()
	{
		return $this->data['status'];
	}

}
