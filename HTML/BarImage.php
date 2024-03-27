<?php

class BarImage
{

	public $expires = 31536000; //60*60*24*365;	// days

	public $width;

	public $height;

	/**
	 * @var array
	 */
	public $color;

	/**
	 * @var array
	 */
	public $backColor;

	public $symmetric = false;

	public $withBorder = true;

	public function __construct()
	{
		$this->width = $_GET['width'] ?? 100;
		$this->height = $_GET['height'] ?? 15;
		$color = $_GET['color'] ?? null;
		$this->color = $color ? $this->html2rgb($color) : [0x43, 0xB6, 0xDF]; #43B6DF
		$bg = $_GET['bg'] ?? null;
		$this->backColor = $bg ? $this->html2rgb($bg) : [0xFF, 0xFF, 0xFF];
		$this->symmetric = ifsetor($_REQUEST['symmetric']);
		$this->withBorder = !ifsetor($_GET['!border']);
	}

	public function html2rgb($color)
	{
		if ($color[0] === '#') {
			$color = substr($color, 1);
		}

		if (strlen($color) === 6)
			list($r, $g, $b) = [$color[0] . $color[1],
				$color[2] . $color[3],
				$color[4] . $color[5]];
		elseif (strlen($color) === 3)
			list($r, $g, $b) = [$color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]];
		else
			return false;

		$r = hexdec($r);
		$g = hexdec($g);
		$b = hexdec($b);

		return [$r, $g, $b];
	}

	public function setHeaders()
	{
		error_reporting(E_ALL);
		//ini_set('display_errors', false);
		header("Content-Type: image/png");
		header("Pragma: public");
		header("Cache-Control: maxage=" . $this->expires);
		header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $this->expires) . ' GMT');
	}

	public function drawRating($rating)
	{
		$ratingbar = ($rating / 100) * ($this->width - 5);
		$barDX = 2;
		$image = imagecreate($this->width, $this->height);

		$backColor = $this->backColor;
		$back = imagecolorallocate($image, $backColor[0], $backColor[1], $backColor[2]);
		$border = imagecolorallocate($image, 127, 127, 127);
		imagefilledrectangle($image, 0, 0, $this->width - 1, $this->height - 1, $back);

		if ($this->withBorder) {
			imagerectangle($image, 0, 0, $this->width - 1, $this->height - 1, $border);
		} else {
			$ratingbar += 2;
			$barDX = 0;
		}

		$color = $this->color;
		$fill = imagecolorallocate($image, $color[0], $color[1], $color[2]);
		//if ($rating > 49) { $fill = ImageColorAllocate($image,255,255,0); }
		//if ($rating > 74) { $fill = ImageColorAllocate($image,255,128,0); }
		//if ($rating > 89) { $fill = ImageColorAllocate($image,255,0,0); }
		if ($this->symmetric) {
			$middle = $this->width / 2;
			imagefilledrectangle($image, $middle + $barDX, $barDX, $middle + $barDX + $ratingbar / 2, $this->height - $barDX - 1, $fill);
		} else {
			imagefilledrectangle($image, $barDX, $barDX, $barDX + $ratingbar, $this->height - $barDX - 1, $fill);
		}
		imagepng($image);
		imagedestroy($image);
	}

}
