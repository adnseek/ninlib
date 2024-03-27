<?php

class TimeTrackHG extends AppControllerBE
{

	public $cacheFile;

	public function __construct()
	{
		parent::__construct();
		$this->cacheFile = AutoLoad::getInstance()->getAppRoot() . 'cache/' . __CLASS__;
	}

	public function render()
	{
		$content[] = $this->performAction($this->detectAction());
		if (file_exists($this->cacheFile)) {
			$times = file_get_contents($this->cacheFile);
			$times = json_decode($times, true);

			$total = $this->showTotal($times);
			$who = $this->showByWho($times);
			$content[] = $this->inTable([$total, $who]);
			$content[] = $this->listTimes($times);
		}
		return $content;
	}

	public function showTotal(array $times)
	{
		$ap = ArrayPlus::create($times);
		$sum = $ap->column('time')->sum();
		$percentage = 100;
		$content[] = '<div class="">
			<h3>Total</h3>
			<h1>' . $sum . ' hours
				<span class="small">' . $percentage . '%</span>
			</h1>
		</div>';
		return $content;
	}

	public function showByWho(array $times)
	{
		$ap = ArrayPlus::create($times);
		$groups = $ap->groupBy('who')->sumGroups('time');
		$s = slTable::showAssoc($groups->getData());
		$content[] = '<h3>Group by who</h3>';
		$content[] = $s;
		return $content;
	}

	public function listTimes(array $times)
	{
		foreach ($times as &$line) {
			$line['what'] = preg_replace("/#(\w+)/", "<a href=\"\\1\">#\\1</a>", $line['what']);
		}
		$s = new slTable($times, 'class="table table=striped"');
		$s->generateThes();
		$s->thes['what'] = [
			'name' => 'what',
			'no_hsc' => true,
		];
		return $s;
	}

	public function sidebar()
	{
		$content[] = $this->getActionButton('Parse', 'parse');
		return $content;
	}

	public function parseAction()
	{
		$content = [];
		$cmd = 'hg log';
		@exec($cmd, $output);
		if ($output) {
			$lines = $this->readFile($output);
			$times = $this->parseTime($lines);
			file_put_contents($this->cacheFile, json_encode($times));
		}
		return $content;
	}

	/**
	 * From HYBH
	 * @param array $file
	 * @return array
	 */
	public function readFile(array $file)
	{
		$i = 0;
		$iEmpty = 0;
		$table = [];
		foreach ($file as $line) {
			$line = trim($line);
			list($key, $line) = trimExplode(':', $line, 2);
			$table[$iEmpty][$key] = $line;
			++$i;
			if (!$line) {
				$iEmpty++;
				$i = 0;
			}
		}
		return $table;
	}

	public function parseTime(array $lines)
	{
		$times = [];
		foreach ($lines as $line) {
			//$s = \Stringy\Stringy::create($line['summary']);
			$summaryLines = trimExplode("\n", $line['summary']);
			foreach ($summaryLines as $sumLine) {
				preg_match('/\[(.*)\]/', $sumLine, $squares);
				foreach ($squares as $i => $candidate) {
					if ($i) {
						$dur = Duration::fromHuman($candidate);
						//debug($sumLine, $candidate, $dur);
						if ($dur->getTimestamp()) {
							$times[] = [
								'who' => $line['user'],
								'when' => $line['date'],
								'time' => $dur->getHours(),
								'what' => $line['summary'],
							];
						}
					}
				}
			}
		}
		return $times;
	}

}
