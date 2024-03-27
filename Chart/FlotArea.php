<?php

class FlotArea
{

	/**
	 * @var array
	 */
	public $data;

	/**
	 * @var array
	 */
	public $series;

	/**
	 * @var string
	 */
	public $flotPath = 'components/flot/flot/';

	public function __construct(array $data, array $series)
	{
		$this->data = $data;
		$this->series = $series;
	}

	public function setFlot($path)
	{
		$this->flotPath = $path;
	}

	public function getSeries()
	{
		$series = [];
		ksort($this->data);
		foreach ($this->data as $time => $pair) {
			foreach ($this->series as $key) {
				$series[$key][] = [$time * 1000, $pair[$key]];
			}
		}
		return $series;
	}

	public function accumulateSeries(array $series)
	{
		foreach ($series as &$set) {
			$runningTotal = 0;
			foreach ($set as &$pair) {
				$runningTotal += $pair[1];
				$pair[1] = $runningTotal;
			}
		}
		return $series;
	}

	public function getJSON(array $series)
	{
		foreach ($series as &$set) {
			$set = json_encode($set);
			$set = '[' . substr($set, 1, -1) . ']';  // json_encode => {}
		}
		return $series;
	}

	public function render()
	{
		$series = $this->getSeries();
		$series = $this->accumulateSeries($series);
		$series = $this->getJSON($series);

		$content = '
<script type="text/javascript" language="javascript" src="' . $this->flotPath . 'jquery.js"></script>
<script type="text/javascript" language="javascript" src="' . $this->flotPath . 'jquery.flot.js"></script>
<script type="text/javascript" language="javascript" src="' . $this->flotPath . 'jquery.flot.time.js"></script>';
		$content .= '<div id="placeholder" style="width: 768px; height: 480px;"></div>';
		$content .= "<script type=\"text/javascript\">
$(document).ready(function ($) {
    var d1 = " . $series[$this->series[0]] . ";
    var d2 = " . $series[$this->series[1]] . ";
 
    var data1 = [
        { label: 'Reported', data: d1, points: { fillColor: '#EE3B3B', size: 5 }, color: '#EE3B3B' },
        { label: 'Resolved', data: d2, points: { fillColor: '#76EE00', size: 5 }, color: '#76EE00' }
    ];
 
    $.plot($('#placeholder'), data1, {
        xaxis: {
			//min: (new Date(2009, 12, 1)).getTime(),
            //max: (new Date(2010, 11, 1)).getTime(),
            mode: 'time',
            //tickSize: [1, 'month'],
            //monthNames: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            tickLength: 0,
            //axisLabel: 'Month',
            axisLabelUseCanvas: true,
            axisLabelFontSizePixels: 12,
            axisLabelFontFamily: 'Verdana, Arial, Helvetica, Tahoma, sans-serif',
            axisLabelPadding: 5
        },
        yaxis: {
			axisLabel: 'Amount',
            axisLabelUseCanvas: true,
            axisLabelFontSizePixels: 12,
            axisLabelFontFamily: 'Verdana, Arial, Helvetica, Tahoma, sans-serif',
            axisLabelPadding: 5
        },
        series: {
			lines: {
				show: true, fill: true
            },
			points: {
				show: false
            },
		},
        grid: {
			borderWidth: 1
        },
        legend: {
			labelBoxBorderColor: 'none',
            position: 'right'
        }
    });
});
</script>";
		return $content;
	}

}
