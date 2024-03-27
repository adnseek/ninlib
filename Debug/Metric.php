<?php

/**
 * pdepend --summary-xml=pdepend-summary.xml class/Home/ && php metric.php
 * Class Metric
 */
class Metric
{

	protected $save;

	protected $thresholds = [
		'cyclo-loc' => [0.16, 0.20, 0.24],
		'cyclo-nom' => [0, 6, 12],
		'loc-nom' => [7, 10, 13],
		'nom-noc' => [4, 7, 10],
		'noc-nop' => [6, 17, 26],
		'calls-nom' => [2.01, 2.62, 3.2],
		'fanout-calls' => [0.56, 0.62, 0.68],
		'andc' => [0.25, 0.41, 0.57],
		'ahh' => [0.09, 0.21, 0.32],
	];

	public function __construct()
	{
//		$this->testPercentage();
	}

	public function render()
	{
		$this->save = true;

		$attr = $this->readAttrFromFile();
		$props = $this->computeProportions($attr);

		$last = $this->readLast($attr + $props);

		$this->renderTable($attr + $props, $last);

		$p1 = 0;
		if ($last) {
			$p1 = $this->showTotalProgress($last);
			echo 'Metric from prev. run: ', $p1, PHP_EOL;
		}
		$p2 = $this->showTotalProgress($props);
		echo 'Single quality metric: ', $p2, PHP_EOL;
		if ($last) {
			echo 'Improvement: ', ($p1 - $p2), '%', PHP_EOL;
		}

//		debug($last['generated'], $attr['generated']);
		$save = $this->save && ifsetor($last['generated']) != $attr['generated'];
		if ($save) {
			file_put_contents(
				getcwd() . '/metric.log',
				json_encode($attr + $props) . PHP_EOL,
				FILE_APPEND
			);
		}
	}

	protected function readAttrFromFile()
	{
		$file = getcwd() . '/pdepend-summary.xml';
		$xml = simplexml_load_file($file);
		$attr = (array)($xml->attributes());
//$attr = (array)($xml->package[0]->class[0]->attributes());
		$attr = $attr['@attributes'];
		$attr['cyclo'] = $attr['cloc'];
		return $attr;
	}

	protected function readLast(array $combined)
	{
		$logLines = file(getcwd() . '/metric.log');
		$last = end($logLines);
		if ($last) {
			$last = json_decode($last, true);
		}
		if ($last == $combined) {
			$last = $logLines[sizeof($logLines) - 2];  // prev last
			$last = json_decode($last, true);
			$this->save = false;
			echo 'Saving is disabled', PHP_EOL;
		}
		return $last;
	}

	protected function renderTable(array $combined, array $last)
	{
		foreach ($combined as $name => $value) {
			$warning = null;
			$limits = ifsetor($this->thresholds[$name]);
			if ($limits) {
				$percent = $this->getPercentage($value, $limits) * 100;

				if ($value < $limits[0]) {
					$warning = 'Too low (' . round($percent, 2) . '%)';
				} elseif ($value > $limits[2]) {
					$warning = 'Too high (' . round($percent, 2) . '%)';
				} else {
					$warning = 'OK (' . (($percent > 0) ? '+' : '') . round($percent, 2) . '%)';
				}
			}

			$lastTime = null;
			if (ifsetor($last[$name]) != $value) {
				$lastTime = 'was ' . $last[$name];
			}
			echo tabify([$name,
				$limits ? '[' . $limits[0] . '..' . $limits[2] . ']'
					: TAB . TAB,
				$lastTime, $value, $warning]), PHP_EOL;
		}
	}

	protected function getPercentage($value, array $limits)
	{
//		if (($value >= $limits[0]) && ($value <= $limits[2])) {
		$range = $limits[2] - $limits[1];
		$num = -1 + ($value - $limits[0]) / $range;
		return $num;
//		}
		return null;
	}

	protected function showTotalProgress(array $props)
	{
		$progress = array_reduce(array_keys($props),
			function ($acc, $code) use ($props) {
				$value = $props[$code];
				if (isset($this->thresholds[$code])) {
					$limits = $this->thresholds[$code];
					$percent = $this->getPercentage($value, $limits);
					if ($percent != 0) {
						return $acc * $percent;
					}
				}
				return $acc;
			}, 1);
		return sqrt(abs($progress)) * 100 . '%';
	}

	/**
	 * Computes the proportions between the given metrics.
	 *
	 * @param array $metrics The aggregated project metrics.
	 * @return array(string => float)
	 */
	protected function computeProportions(array $metrics)
	{
		$orders = [
			['cyclo', 'loc', 'nom', 'noc', 'nop'],
			['fanout', 'calls', 'nom'],
		];

		$proportions = [];
		foreach ($this->thresholds as $names => $_) {
			$names = trimExplode('-', $names);
			for ($i = 1, $c = count($names); $i < $c; ++$i) {
				$value1 = $metrics[$names[$i]];
				$value2 = $metrics[$names[$i - 1]];

				$identifier = "{$names[$i - 1]}-{$names[$i]}";

				$proportions[$identifier] = 0;
				if ($value1 > 0) {
					$proportions[$identifier] = round($value2 / $value1, 3);
				}
			}
		}

		return $proportions;
	}

	protected function testPercentage()
	{
		$this->thresholds['test'] = [10, 15, 20];
		$percentageTests = [
			0 => null,
			10 => -1,
			15 => 0,
			20 => 1,
			25 => 2,
		];
		echo tabify(['source', 'calc', 'must']), PHP_EOL;
		foreach ($percentageTests as $source => $must) {
			$calc = $this->getPercentage(
				$source, $this->thresholds['test']);
			echo tabify([$source, $calc, $must]), PHP_EOL;
		}
	}

}
