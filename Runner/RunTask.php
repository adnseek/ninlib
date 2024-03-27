<?php

/**
 * Class RunTask. We need to spawn tasks using this wrapper because
 * each task has to store it's PID into the database.
 * Well, not anymore. Since we made proc_open able to get PID.
 * Well, this PID is of the parent cmd process not the PHP process.
 */
class RunTask extends AppControllerBE
{

	public function render()
	{
		$id = ifsetor($_SERVER['argv'][2]);
		if ($id) {
			$task = new RunnerTask();
			$task->fetch($id);
			if ($task->isValid()) {
				$task->reserve();
				$task();
			}
		}
	}

}
