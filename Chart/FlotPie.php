<?php

use spidgorny\nadlib\HTTP\URL;

/**
 * Class Flot - is drawing a flot chart.
 */
class FlotPie extends AppControllerBE
{

	/**
	 * Raw data single table
	 * @var array
	 */
	public $data;
	/**
	 * @var string
	 */
	public $flotPath = 'components/flot/flot/';
	protected $colors = [
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
	 * @param array $data - source data
	 */
	public function __construct(array $data)
	{
		parent::__construct();
		$this->data = $data;
	}

	public function render()
	{
		$content = '';
		$content .= $this->showChart('chart1');
		return $content;
	}

	public function showChart($divID)
	{
		Index::getInstance()->addJQuery();
		Index::getInstance()->footer['flot'] = '
		<!--[if lte IE 8]><script language="javascript" type="text/javascript" src="flot/excanvas.min.js"></script><![endif]-->
    	<script language="javascript" type="text/javascript" src="' . $this->flotPath . 'jquery.flot.js"></script>
    	<script language="javascript" type="text/javascript" src="' . $this->flotPath . 'jquery.flot.pie.js"></script>';

		$content = '<div id="' . $divID . '" style="width: 950px; height:600px; border: none 0px silver;"></div>';

		$charts = [];
		$dKeys = [];
		foreach ($this->data as $key => $val) {
			$jsKey = 'd_' . \spidgorny\nadlib\HTTP\URL::friendlyURL($key);
			$jsKey = str_replace('-', '_', $jsKey);
			$dKeys[] = $jsKey;
			$charts[] = 'var ' . $jsKey . ' = {
				label: "' . $key . '",
				data: ' . $val . ',
			};';
		}

		Index::getInstance()->footer[$divID] = '
    	<script type="text/javascript">
    function labelFormatter(label, series) {
		return "<div style=\'font-size:8pt; text-align:center; padding:2px; color:white;\'>" + label + "<br/>" + Math.round(series.percent) + "%</div>";
	}
$(function ($) {
	' . implode("\n", $charts) . '
    $.plot($("#' . $divID . '"), [
    	' . implode(", ", $dKeys) . '
    ], {
    	series: {
     		pie: {
           		show: true,
           		radius: 1,
				label: {
					show: true,
					radius: 3/4,
					formatter: labelFormatter,
					background: {
						opacity: 0.5
					}
				}
        	}
        },
    	colors: ' . json_encode($this->colors) . '
    });
});
</script>';
		return $content;
	}

}
