<?php

class SVGChart
{

	protected $width;
	protected $height;
	protected $data = [];

	public $text_size = 14;

	protected $id;

	public function __construct($width = '100%', $height = '50', array $data = [])
	{
		$this->width = $width;
		$this->height = $height;
		$this->setData($data);
		$this->id = uniqid();
	}

	public function setData(array $data)
	{
		$this->data = $data;
	}

	public function render()
	{
		$content = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1"
		width="' . $this->width . '"
		height="' . $this->height . '">' . "\n";
		$height = $this->height;
		if (sizeof($this->data)) {
			$max = max($this->data);
			$width = max(2, round($this->width / sizeof($this->data)));
			$labels = array_keys($this->data);
			$every = 0;
			$data = array_values($this->data);
			$often = ceil(sizeof($data) / 4);
			foreach ($data as $i => $height) {
				$x = $i * $width;
				$height = round(($this->height - $this->text_size) * $height / $max);
				$content .= '<rect
					id="rect_' . $this->id . '_' . $i . '"
					x="' . $x . '"
					y="' . ($this->height - max(1, $height) - $this->text_size) . '"
					width="' . ($width - 1) . '"
					height="' . max(1, $height) . '"
					style="fill:#a9d1df;stroke-width:0;stroke:rgb(0,0,0)" />' . "\n";
				if (!($every++ % $often)) {
					$content .= '<text
						x="' . ($x) . '"
						y="' . ($this->height - 5) . '"
						font-size="' . ($this->text_size - 3) . '">' . $labels[$i] . '</text>' . "\n";
				}
			}
			$diff = (max($this->data) - min($this->data)) / sizeof($this->data);
			foreach ($data as $i => $times) {
				$x = $i * $width;
				$y = $this->height - max(1, $height) - 7;
				$height = round(($this->height - 20) * $times / $max);
				$i2 = round($labels[$i + 1]);
				$text = $labels[$i];
				if ($i2) {
					$text .= ' - ' . $i2;
				}
				$text .= ': ' . $times . ' times';
				$x = 0;
				$y = $this->text_size;
				$content .= '<text id="thepopup' . $i . '"
					x="' . $x . '"
					y="' . $y . '"
					fill="black"
					visibility="hidden"
					text-size="' . $this->text_size . '">' . $text . '
					<set attributeName="visibility" from="hidden" to="visible"
					begin="rect_' . $this->id . '_' . $i . '.mouseover" end="rect_' . $this->id . '_' . $i . '.mouseout" />
				</text>';
			}
		}
		$content .= '</svg>';
		return $content;
	}

	public function __toString()
	{
		return $this->render() . '';
	}

}
