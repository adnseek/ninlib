<?php

/**
 * Class RunDispatcher. Usage:
 * > php index.php RunDispatcher
 * it will watch the queue and start new processes
 * as soon as resources are available.
 */
class RunDispatcher extends AppControllerBE
{

	public static $public = true;

	public $runner;

	public $parallelism = 3;

	/**
	 * @var RunnerTask[]
	 */
	public $processes = [];

	public function __construct()
	{
		parent::__construct();
		$this->runner = new Runner();
	}

	public function render()
	{
		while (true) {
			$queue = $this->runner->getTaskQueue();
			echo getmypid(),
			TAB, 'Active processes: ', count($this->processes),
			TAB, 'Max: ', $this->parallelism,
			TAB, 'Queue: ', count($queue), BR;
			$command = RunnerTask::getNext();  // without reserve()
			if ($command) {
				$command->release();  // we are not going to process it
				$this->start($command);
			}

			// sleep at least once or more if too many processes
			do {
				sleep(1);
				$this->checkLiveProcesses();
			} while (count($this->processes) >= $this->parallelism);
		}
	}

	public function start(RunnerTask $task)
	{
		echo '> ', $task->getName(), '(', implode(', ', $task->getParams()), ')', BR;
//		$cmd = $this->getTaskCommandLine();
		$cmd = 'php index.php RunTask ' . $task->id();
		echo '> ', $cmd, BR;
		$start = 'start "' . $task->getName() . '" ' . $cmd;
		//exec($start);
//		$process = popen($cmd, "r");
//		$process = proc_open('cmd /c '.$start, array(
		$process = proc_open($start, [
			["pipe", "r"],
			["pipe", "w"],
			["pipe", "w"],
		], $pipes);
		$pinfo = proc_get_status($process);
		$task->pinfo = $pinfo;
		echo 'Start: ' . $pinfo['pid'], BR;
		proc_close($process);  // don't wait to finish? maybe?
		$this->processes[] = $task;
		return $cmd;
	}

	public function getTaskCommandLine(RunnerTask $task) {
		$cmd = 'php index.php '.get_class($task->obj).' '.$task->method;
		$params = $task->getParams();
		if ($params) {
			$rMethod = new ReflectionMethod($task->obj, $task->method);
			foreach ($rMethod->getParameters() as $i => $param) {
				$cmd .= ' -'.$param->getName().' '.$params[$i];
			}
		}
		return $cmd;
	}

	public function checkLiveProcesses() {
		echo 'Active Processes: ', TAB, 'max: ', $this->parallelism, BR;
		/**
		 * @var int $p
		 * @var RunnerTask $task
		 */
		foreach ($this->processes as $p => $task) {
			// getting the PID of "start" process is not helpful
			$pidOfStart = $task->pinfo['pid'];
			$pid = $task->getPID();
			echo TAB, '* ', $task->id(), TAB, $task->getName(),
			TAB, 'PID of Start: ', $pidOfStart,
			TAB, 'PID: ', $pid, BR;
			if ($pid) {    // it make not have started yet
				$cmd = 'tasklist /fi "PID eq ' . $pid . '"';
				$output = [];
				exec($cmd, $output);
				//print_r($output);
				//echo TAB, TAB, sizeof($output), BR;
				if (count($output) < 2) {    // otherwise min 4 lines
					unset($this->processes[$p]);
				}
			}
		}
	}

	/**
	 * Used for testing
	 */
	public function spam()
	{
		foreach (range(30, 40) as $s) {
			$task = RunnerTask::schedule(__CLASS__, 'sleepFor', [$s]);
			echo '* ', $task->id(), ' ', $task->getName(), BR;
		}
	}

	public function sleepFor($seconds)
	{
		echo __METHOD__ . '(' . $seconds . ')', ' PID: ', getmypid(), BR;
		$start = microtime(true);
		do {
			sleep(1);
			$duration = microtime(true) - $start;
			echo 'Duration: ', $duration, BR;
		} while ($duration < $seconds);
		echo 'Done. Bye-bye', BR;
	}

}
