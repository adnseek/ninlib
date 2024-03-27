<?php

class Timeline /*extends AppController */
{

	/**
	 * @var Date
	 */
	public $start;

	/**
	 * @var Date
	 */
	public $end;

	public $width;

	public $height;

	public $fillBottomColor = '#726D62';

	public $fillTopColor = '#D0D0D0';

	public $rangeColor = '#42383D';

	public $textColor = 'rgb(200,200,200)';

	/** @var Duration */
	public $duration;

	public function __construct($width, $height, Time $start, Time $end)
	{
		$this->width = $width;
		$this->height = $height;
		$this->start = $start;
		$this->end = $end;
		$this->duration = $this->end->minus($this->start);
	}

	public function render(Date $from, Date $till)
	{
		$content = '';
		if ($this->duration->getTimestamp()) {
			$content = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="' . $this->width . '" height="' . $this->height . '">' . "\n";
			$height_10 = $this->height - 15;
			$height_20 = $this->height - 20;
			$height_30 = $this->height - 30;
			$content .= '<rect
				x="' . (0) . '"
				y="' . $height_30 . '"
				width="' . ($this->width) . '"
				height="' . (30) . '"
				style="fill:' . $this->fillBottomColor . ';stroke-width:0;stroke:rgb(0,0,0)" />' . "\n";
			for ($date = clone $this->start/* @var $date Date */;
					 $date->earlier($this->end);
					 $date->add(new Duration('1 day'))) {
				$x = $this->date2x($date);
				$content .= '<line x1="' . $x . '" y1="' . ($this->height - 5) . '" x2="' . $x . '" y2="' . $this->height . '"
					style="stroke:' . $this->textColor . ';stroke-width:1"/>';
			}
			for ($date = clone $this->start/* @var $date Date */;
					 $date->earlier($this->end);
					 $date->add(new Duration('1 week'))) {
				$x = $this->date2x($date);
				$content .= '<line x1="' . $x . '" y1="' . $height_10 . '" x2="' . $x . '" y2="' . $this->height . '"
					style="stroke:' . $this->textColor . ';stroke-width:1"/>';
				$content .= '<text
					x="' . ($x + 1) . '"
					y="' . ($height_20 + 13) . '"
					fill="' . $this->textColor . '">' . $date->format('W') . '</text>';
			}
			for ($date = clone $this->start/* @var $date Date */;
					 $date->earlier($this->end);
					 $date->add(new Duration('1 month'))) {
				$x = $this->date2x($date);
				$content .= '<line x1="' . $x . '" y1="' . $height_30 . '" x2="' . $x . '" y2="' . $this->height . '"
					style="stroke:' . $this->textColor . ';stroke-width:1"/>';
				$content .= '<text
					x="' . ($x + 1) . '"
					y="' . ($height_30 + 11) . '"
					fill="' . $this->textColor . '">' . $date->format('M') . '</text>';
			}

			$content .= '<rect
				x="' . (0) . '"
				y="' . (0) . '"
				width="' . $this->width . '"
				height="' . ($this->height - $height_20) . '"
				style="fill:' . $this->fillTopColor . '; stroke-width:0; stroke:rgb(0,0,0)" />' . "\n";

			$x = $this->date2x($from);
			$width = $this->date2x($till) - $x;
			$content .= '<rect
				x="' . $x . '"
				y="' . (0) . '"
				width="' . $width . '"
				height="' . ($this->height - $height_20) . '"
				style="fill:' . $this->rangeColor . '; stroke-width:0; stroke:rgb(0,0,0)" />' . "\n";
			$content .= '</svg>';
		}
		return $content;
	}

	public function date2x(Date $date)
	{
		$sinceStart = $date->minus($this->start);
		//$tillEnd = $this->end->minus($date);
		$percent = $sinceStart->getTimestamp() / ($this->duration->getTimestamp());
		$rc = $this->duration->getTimestamp() / 60 / 60 / 24;
		$percent = round($percent * $rc) / $rc;
		//debug($date->getTimestamp(), $this->start->getTimestamp(), $sinceStart->getTimestamp(), $this->duration->getTimestamp(), $percent);
		return round($percent * $this->width);
	}

}
