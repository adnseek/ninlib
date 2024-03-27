<?php

class RunnerTaskController extends AppControllerBE
{

	/**
	 * @var RunnerTask
	 */
	public $task;

	public function __construct(RunnerTask $task)
	{
		parent::__construct();
		$this->task = $task;
	}

//	abstract function runTask();

}
