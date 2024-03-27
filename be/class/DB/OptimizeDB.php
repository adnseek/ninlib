<?php

/**
 * Class OptimizeDB
 * Calling OPTIMIZE TABLE X; for each table
 */
class OptimizeDB extends AppControllerBE
{

	public function render()
	{
		$tables = $this->db->getTables();
		$content = getDebug($tables);
		return $content;
	}

	public function sidebar()
	{
		return $this->getActionButton('Optimize DB', 'optimize');
	}

	public function optimizeAction()
	{
		$tables = $this->db->getTables();
		$pb = new ProgressBar();
		foreach ($tables as $i => $name) {
			$query = "OPTIMIZE TABLE `" . $name . "`";
			echo $query;
			$this->db->perform($query);
			echo "\n";
			$pb->setProgressBarProgress($i * 100 / sizeof($tables));
		}
	}

}
