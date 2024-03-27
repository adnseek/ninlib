<?php

use spidgorny\nadlib\HTTP\URL;

/**
 * Class Flot - is drawing a flot chart.
 */
class Flot extends Controller
{

	public $colors = [
		'#edc240',
		'#afd8f8',
		'#cb4b4b',
		//'#edc240',
		//'#afd8f8',
		//'#cb4b4b',
		"#9440ed",
		"#40ed94",
		'#4da74d',
	];

	/**
	 * Raw data single table
	 * @var array
	 */
	public $data;

	protected $keyKey;
	protected $timeKey;
	protected $amountKey;

	/**
	 * A source table pivoted (grouped by) $keyKey
	 * @var array
	 */
	public $chart;

	/**
	 * @var array - these are line charts, multiple series as well
	 */
	public $cumulative = [];

	public $movingAverage = [];

	public $min = 0;

	/**
	 * @var int - max value for cumulative (max of max possible)
	 */
	public $max = 1;

	public $cMin = 0;

	public $cMax = 1;

	public $width = '950px';

	public $height = '600px';

	public $barWidth = '24*60*60*1000*25';

	/**
	 * @var string
	 */
	public $flotPath = 'components/flot/flot/';

	/**
	 * Inject
	 * "ticks" => "tickWeeks"
	 * to show weekly data
	 * @var array
	 */
	public $jsConfig = [
		'xaxis' => [
			'mode' => "time",
			"tickFormatter" => "(val, axis) => {
						console.log(val);
						return val.toFixed(axis.tickDecimals);
			}",
		],
		'yaxes' => [
			[],
			[
				'position' => "right"
			]
		],
	];

	public $MALength = 20;

	/**
	 * @param array $data - source data
	 * @param string|null $keyKey - group by field (distinct charts, lines)
	 * @param string $timeKey - time field
	 * @param string $amountKey - value (numeric) field
	 */
	public function __construct(array $data, $keyKey, $timeKey, $amountKey)
	{
		parent::__construct();
		$this->data = $data;
		$this->keyKey = $keyKey;
		$this->timeKey = $timeKey;
		$this->amountKey = $amountKey;

		$this->chart = $this->getChartTable($this->data);
		$this->min = $this->getChartMax($this->chart, 'min');
		$this->max = $this->getChartMax($this->chart);

		$this->cumulative = $this->getChartCumulative($this->chart);
		$this->cMin = $this->getChartMax($this->cumulative, 'min');
		$this->cMax = $this->getChartMax($this->cumulative);

		$this->setMinMax();

		// add this manually before rendering if needed
		//$this->cumulative = $this->getChartCumulative($this->chart);
		//$this->max = $this->getChartMax($this->cumulative);
	}

	public function setFlotPath($path)
	{
		$this->flotPath = $path;
	}

	public function setMinMax()
	{
		$this->jsConfig['colors'] = $this->colors;

		$this->jsConfig['yaxes'][0] = [
			'min' => $this->min,
			'max' => $this->max,
		];
		$this->jsConfig['yaxes'][1] += [
			'min' => $this->cMin,
			'max' => $this->cMax,
		];
	}

	/**
	 * Fixed for Posa Cards
	 *
	 * @param string $divID
	 * @return string
	 * array[19]
	 * 1309471200    array[2]
	 * 0    integer    1309471200000
	 * 1    integer    0
	 * 1314828000    array[2]
	 * 0    integer    1314828000000
	 * 1    integer    39
	 * @throws Exception
	 */
	public function render($divID = 'chart1')
	{
		$content = '';
		if (!is_dir($this->flotPath)) {
			throw new Exception($this->flotPath . ' is not correct');
		}
		$content .= $this->showChart($divID, $this->chart);
		return $content;
	}

	public function renderCumulative($divID = 'chart1')
	{
		$content = '';
		$content .= $this->showChart($divID, $this->chart, $this->cumulative);
		return $content;
	}

	public function appendCumulative(array $data)
	{
		//debug($this->cumulative, $data);
		$cumulative2 = [];
		foreach ($this->cumulative as $series) {
			$cumulative2 = array_merge($cumulative2, $series);
		}
		//$cumulative = array_values($cumulative2);
		$dataClass = [];
		foreach ($data as $i => &$row) {
			$color = $this->colors[$row[$this->keyKey] - 1];
			$dataClass[$i] = '" style="background: white; color: ' . $color;
			//$row['###TD_CLASS###'] = '" style="background: white; color: '.$color;

			//$row['cumulative'] = $cumulative[$i][1];
			$jsTime = strtotime($i) * 1000;
			$row['cumulative'] = $this->cumulative['Total'][$jsTime][1];
		}
		return $data;
	}

	/**
	 * http://bytes.com/topic/php/answers/747586-calculate-moving-average
	 * @return string
	 */
	public function renderMovingAverage()
	{
		$content = '';
		$charts = $this->getChartTable($this->data);
		$this->movingAverage = $this->getMovingAverage($charts);
		$content .= $this->showChart('chart1', $charts, $this->movingAverage);
		return $content;
	}

	public function getMovingAverage(array $charts)
	{
		foreach ($charts as $s => &$series) {
			$res = [];
			foreach ($series as $pair) {
				$res[$pair[0]] = $pair[1];
			}

			$i = 0;
			foreach ($res as &$row) {
				$slice = array_slice($res, max($i - $this->MALength + 1, 0), $this->MALength);
				$row = round(
					array_sum($slice) /
					count($slice),
					4
				);
				$i++;
			}

			foreach ($res as $key => &$val) {
				$val = [$key, $val];
			}
			$series = $res;
		}
		return $charts;
	}

	/**
	 * Return a multitude of rows which are extracted by the $keyKey.
	 * Each row is an assoc array with $timeKey keys and $amountKey values.
	 * Uses strtotime() so the $timeKey values should be PHP parsable
	 *
	 * @param array $rows
	 * @return array
	 * @internal param string $timeKey
	 * @internal param string $amountKey
	 * @internal param string $keyKey
	 */
	public function getChartTable(array $rows)
	{
		$chart = [];
		foreach ($rows as $i => $row) {
			$key = $this->keyKey ? $row[$this->keyKey] : 'one';
			$timeMaybe = $row[$this->timeKey];
			if ($timeMaybe) {
				$time = is_string($timeMaybe) ? strtotime($timeMaybe) : $timeMaybe;
				$barHeight = $row[$this->amountKey];
				if (is_string($barHeight)) {
					$barHeight = strtotime($barHeight);
				}
				if ($time != -1 && $time > 100) {
					$chart[$key][$time] = [$time * 1000, $barHeight];
				} else {
					$chart[$key][$time] = [$timeMaybe, $barHeight];
				}
			} else {
				unset($rows[$i]);
			}
		}
		//debug($chart);
		return $chart;
	}

	public function getChartCumulative(array $chart)
	{
		foreach ($chart as &$sub) {
			ksort($sub);
			$sum = 0;
			foreach ($sub as &$val) {
				$sum += $val[1];
				$val[1] = $sum;
			}
			unset($val);
		}
		return $chart;
	}

	public static function getChartMax(array $chart, $min = false)
	{
		$max = 0;
		foreach ($chart as $series) {
			foreach ($series as $pair) {
				$max = $min ? min($max, $pair[1]) : max($max, $pair[1]);
			}
		}
		return $max;
	}

	public function showChart($divID, array $charts, array $cumulative = [])
	{
		if (!$charts) {
			return '';
		}
		$this->index->addJQuery();
		$this->index->footer['flot'] = '
		<!--[if lte IE 8]><script language="javascript" type="text/javascript"
			src="' . $this->flotPath . 'excanvas.min.js"></script><![endif]-->
    	<script language="javascript" type="text/javascript" defer="1"
    	    src="' . $this->flotPath . 'jquery.canvaswrapper.js"></script>
		<script language="javascript" type="text/javascript" defer="1"
    	    src="' . $this->flotPath . 'jquery.colorhelpers.js"></script>
		<script language="javascript" type="text/javascript" defer="1"
    	    src="' . $this->flotPath . 'jquery.flot.js"></script>
		<script language="javascript" type="text/javascript" defer="1"
    	    src="' . $this->flotPath . 'jquery.flot.saturated.js"></script>
		<script language="javascript" type="text/javascript" defer="1"
    	    src="' . $this->flotPath . 'jquery.flot.browser.js"></script>
		<script language="javascript" type="text/javascript" defer="1"
    	    src="' . $this->flotPath . 'jquery.flot.drawSeries.js"></script>
		<script language="javascript" type="text/javascript" defer="1"
    	    src="' . $this->flotPath . 'jquery.flot.uiConstants.js"></script>
    	<script language="javascript" type="text/javascript" defer="1"
    	    src="' . $this->flotPath . 'jquery.flot.stack.js"></script>
    	<script language="javascript" type="text/javascript" defer="1"
    	    src="' . $this->flotPath . 'jquery.flot.hover.js"></script>
    	<script language="javascript" type="text/javascript" defer="1"
    	    src="' . $this->flotPath . 'jquery.flot.time.js"></script>
    	<script language="javascript" type="text/javascript" defer="1"
    	    src="' . $this->flotPath . 'jquery.flot.axislabels.js"></script>
';

		$content = '<div id="' . $divID . '" style="
			width: ' . $this->width . ';
			height: ' . $this->height . ';
			border: none 0px silver;"></div>';

		$dKeys = [];
		foreach ($charts as $key => &$rows) {
			$jsKey = 'd_' . URL::friendlyURL($key);
			$jsKey = str_replace('-', '_', $jsKey);
			$dKeys[] = $jsKey;
			$array = $rows ? array_values($rows) : [];
			$rows = 'var ' . $jsKey . ' = {
				label: "' . $key . '",
				data: ' . json_encode($array) . ',
				stack: true,
				bars: {
					show: true,
					barWidth: ' . $this->barWidth . ',
					align: "center"
				}
			};';
		}

		$cKeys = [];
		foreach ($cumulative as $key => &$rows) {
			$jsKey = 'c_' . URL::friendlyURL($key);
			$jsKey = str_replace('-', '_', $jsKey);
			$cKeys[] = $jsKey;
			$array = $rows ? array_values($rows) : [];
			$rows = 'var ' . $jsKey . ' = {
				data: ' . json_encode($array) . ',
				lines: {
					show: true,
					fill: false
				},
				yaxis: 2
			};';
		}
		//$max *= 2;

		$config = json_encode($this->jsConfig, defined('JSON_PRETTY_PRINT')
			? JSON_PRETTY_PRINT : null);
		if (false !== strpos($config, 'ticksWeeks')) {
			$al = AutoLoad::getInstance();
			$this->index->addJS($al->nadlibFromDocRoot . 'js/flot-weeks.js');
			$config = str_replace('"ticksWeeks"', 'ticksWeeks', $config); // hack
		}
		$this->index->footer[$divID] = '
    	<script type="text/javascript">
var deferCounter = 0;
function defer(method) {
	if (deferCounter++ > 10) {
		return;
	}
	if (window.jQuery && window.jQuery.plot) {
		console.log(\'jQuery and plot OK\');
		method();
	} else {
		console.log(\'no jQuery or no plot\');
		setTimeout(function() { 
			defer(method) 
		}, 1000);
	}
}
defer(function () {
	jQuery("document").ready(function ($) {
		' . implode("\n", $charts) . '
		' . implode("\n", $cumulative) . '
		$.plot($("#' . $divID . '"), [
			' . implode(", ", $dKeys) . ',
			' . implode(", ", $cKeys) . '
		], ' . $config . ');
	});
});
</script>';
		return $content;
	}

}
