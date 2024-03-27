<?php

class MiniProfiler
{
	protected $timer = [];
	protected $lastName = [];

	public function startTimer($name)
	{
		$this->timer[$name]['start'] = microtime(true);
		//$this->lastName[] = $name;
	}

	public function stopTimer($name)
	{
		$this->timer[$name]['duration'] += microtime(true) - $this->timer[$name]['start'];
		$this->timer[$name]['times']++;
		$this->timer[$name]['start'] = microtime(true);
		//$this->lastName[] = $name;
	}

	public function printTimers()
	{
		$table = [];
		foreach ($this->timer as $name => $t) {
			$table[] = [
				'name' => $name,
				'duration' => number_format($t['duration'], 3, '.', ''),
				'times' => $t['times'],
			];
		}
		//$ac = array_column($table, 'duration');
		$ac = ArrayPlus::create($table)->column('duration')->getData();
		array_multisort($ac, SORT_DESC, $table);
		return new slTable($table);
	}

}
