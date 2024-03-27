<?php

class Runner
{

	/**
	 * @var DBInterface
	 */
	public $db;

	/**
	 * @var RunnerTask
	 */
	public $currentTask;

	public function __construct()
	{
		$this->db = Config::getInstance()->getDB();
	}

	public function run()
	{
		echo 'Ready...', BR;
		while (true) {
			/** @var RunnerTask $command */
			$command = $this->getNextCommand();
			if ($command) {
				$command();
			} else {
				echo 'Nothing to do for ' . TaylorProfiler::getElapsedTimeString() . ' :-(', BR;
			}
			sleep(1);
		}
	}

	public function getNextCommand()
	{
		$task = RunnerTask::getNext();
		if ($task) {
			$task->reserve();
			$this->currentTask = $task;
			if ($task->isValid()) {
				return $task;
			} else {
				$e = new BadMethodCallException('Method ' . $task->getName() . ' is not found.');
				$task->failed($e);
			}
		}
		return null;
	}

	public function getPendingTasks()
	{
		$rows = $this->db->fetchAllSelectQuery('runner', [
			'status' => new SQLOr([
				'status' => new SQLNotIn(['done', 'failed', 'killed']),
				'status ' => null,
			]),
		], 'ORDER BY ctime');
		//debug($this->db->lastQuery);
		return $rows;
	}

	public function getTaskQueue()
	{
		$rows = $this->db->fetchAllSelectQuery('runner', [
			'status' => new SQLOr([
				'status' => '',
				'status ' => null,
			]),
		], 'ORDER BY ctime');
		return $rows;
	}

}
