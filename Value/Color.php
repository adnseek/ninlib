<?php

class Color
{

	/**
	 * @var int
	 */
	public $r, $g, $b;

	public static function fromRGBArray(array $rgb)
	{
		return new Color($rgb);
	}

	public static function fromRGB($r, $g, $b)
	{
		return new Color([$r, $g, $b]);
	}

	public static function fromHEX($rgb)
	{
		assert($rgb[0] === '#');
		assert(strlen($rgb) === 7);
		return new Color($rgb);
	}

	public function __construct($init)
	{
		if ($init[0] === '#') {
			$colourstr = str_replace('#', '', $init);
			$rhex = substr($colourstr, 0, 2);
			$ghex = substr($colourstr, 2, 2);
			$bhex = substr($colourstr, 4, 2);

			$this->r = hexdec($rhex);
			$this->g = hexdec($ghex);
			$this->b = hexdec($bhex);
		} elseif (is_array($init) && sizeof($init) == 3) {
			if (gettype(first($init)) == 'integer') {
				list($this->r, $this->g, $this->b) = $init;
			} elseif (gettype(first($init)) == 'double') {
				throw new InvalidArgumentException('Please use static constructors for HSL and HSV');
			}
		} else {
			throw new InvalidArgumentException('Unable to understand color [' . $init . ']');
		}
	}

	/**
	 * http://stackoverflow.com/questions/5199783/help-needed-with-php-function-brightness-making-rgb-colors-darker-brighter
	 * @param string $colourstr
	 * @param int $steps
	 * @return string
	 */
	public function alter_brightness($colourstr, $steps)
	{
		$r = max(0, min(255, $this->r + $steps));
		$g = max(0, min(255, $this->g + $steps));
		$b = max(0, min(255, $this->b + $steps));

		return $this->getCSS([$r, $g, $b]);
	}

	/**
	 * @param int $deltaHue [0..360]
	 * @param int $deltaSaturation [0..100]
	 * @param int $deltaLightness [0..100]
	 * @return string
	 */
	public function alter_color($deltaHue = 0, $deltaSaturation = 0, $deltaLightness = 0)
	{
		$hsv = $this->RGB_TO_HSV($this->r, $this->g, $this->b);
		$hsl = $this->hsv_to_hsl($hsv['H'], $hsv['S'], $hsv['V']);

		$hsl2[0] = $hsl[0] + $deltaHue / 360;
		$hsl2[1] = $hsl[1] + $deltaSaturation / 100;
		$hsl2[2] = $hsl[2] + $deltaLightness / 100;

		$hsv2 = $this->hsl_to_hsv($hsl2[0], $hsl2[1], $hsl2[2]);

		$rgb = $this->HSVtoRGB($hsv2);
		nodebug([
			'rgb' => [$this->r, $this->g, $this->b],
			'hsv' => $hsv,
			'hsl' => $hsl,
			'hsl2' => $hsl2,
			'hsv2' => $hsv2,
			'rgb2' => $rgb]);

		return $this->getCSS($rgb);
	}

	public function __toString()
	{
		return $this->getCSS([$this->r, $this->g, $this->b]);
	}

	public function getCSS($rgb = null)
	{
		$rgb = array_values($rgb ?: [$this->r, $this->g, $this->b]);
		return '#' .
			str_pad(dechex((int)$rgb[0]), 2, '0', STR_PAD_LEFT) .
			str_pad(dechex((int)$rgb[1]), 2, '0', STR_PAD_LEFT) .
			str_pad(dechex((int)$rgb[2]), 2, '0', STR_PAD_LEFT);
	}

	/**
	 * http://stackoverflow.com/questions/1773698/rgb-to-hsv-in-php
	 * @param int $R
	 * @param int $G
	 * @param int $B
	 * @return array
	 */
	public function RGB_TO_HSV($R, $G, $B)  // RGB Values:Number 0-255
	{                                 // HSV Results:Number 0-1
		$HSL = [];

		$var_R = ($R / 255);
		$var_G = ($G / 255);
		$var_B = ($B / 255);

		$var_Min = min($var_R, $var_G, $var_B);
		$var_Max = max($var_R, $var_G, $var_B);
		$del_Max = $var_Max - $var_Min;

		$V = $var_Max;

		if ($del_Max == 0) {
			$H = 0;
			$S = 0;
		} else {
			$S = $del_Max / $var_Max;

			$del_R = ((($var_Max - $var_R) / 6) + ($del_Max / 2)) / $del_Max;
			$del_G = ((($var_Max - $var_G) / 6) + ($del_Max / 2)) / $del_Max;
			$del_B = ((($var_Max - $var_B) / 6) + ($del_Max / 2)) / $del_Max;

			$H = 0;
			if ($var_R == $var_Max) {
				$H = $del_B - $del_G;
			} elseif ($var_G == $var_Max) {
				$H = (1 / 3) + $del_R - $del_B;
			} elseif ($var_B == $var_Max) {
				$H = (2 / 3) + $del_G - $del_R;
			}

			if ($H < 0) {
				$H++;
			}
			if ($H > 1) {
				$H--;
			}
		}

		$HSL['H'] = $H;
		$HSL['S'] = $S;
		$HSL['V'] = $V;

		return $HSL;
	}

	/**
	 * http://stackoverflow.com/questions/3597417/php-hsv-to-rgb-formula-comprehension
	 * @param array $hsv
	 * @return array
	 */
	public function HSVtoRGB(array $hsv)
	{
		list($H, $S, $V) = $hsv;
		//0
		$H = $H - floor($H);    // not bigger than 360 grad
		//1
		$H *= 6;
		//2
		$I = floor($H);
		$F = $H - $I;
		//3
		$M = $V * (1 - $S);
		$N = $V * (1 - $S * $F);
		$K = $V * (1 - $S * (1 - $F));
		//4
		switch ($I) {
			case 0:
				list($R, $G, $B) = [$V, $K, $M];
				break;
			case 1:
				list($R, $G, $B) = [$N, $V, $M];
				break;
			case 2:
				list($R, $G, $B) = [$M, $V, $K];
				break;
			case 3:
				list($R, $G, $B) = [$M, $N, $V];
				break;
			case 4:
				list($R, $G, $B) = [$K, $M, $V];
				break;
			case 5:
			case 6: //for when $H=1 is given
				list($R, $G, $B) = [$V, $M, $N];
				break;
			default:
				die(__METHOD__ . '#' . __LINE__ . ' ' . $I . ' ' . $H);
		}
		return [$R * 255, $G * 255, $B * 255];
	}

	/**
	 * http://ariya.blogspot.de/2008/07/converting-between-hsl-and-hsv.html
	 * @param int $h
	 * @param int $s
	 * @param int $v
	 * @return array
	 */
	public function hsv_to_hsl($h, $s, $v)
	{
		$hh = $h;
		$ll = (2 - $s) * $v;
		$ss = $s * $v;
		$ss /= ($ll <= 1) ? ($ll) : 2 - ($ll);
		$ll /= 2;
		return [$hh, $ss, $ll];
	}

	public function hsl_to_hsv($hh, $ss, $ll)
	{
		$h = $hh;
		$ll *= 2;
		$ss *= ($ll <= 1) ? $ll : 2 - $ll;
		$v = ($ll + $ss) / 2;
		$s = (2 * $ss) / ($ll + $ss);
		return [$h, $s, $v];
	}

	public function getComplement255()
	{
		$c = new self([
			255 - $this->r,
			255 - $this->g,
			255 - $this->b]);
		return $c;
	}

	public function getComplement()
	{
		$c = $this->alter_color(180, 0, 0);
		return $c;
	}

	/**
	 * https://sighack.com/post/averaging-rgb-colors-the-right-way
	 * https://youtu.be/LKnqECcg6Gw
	 * @param array $colors
	 */
	public static function average(array $colors)
	{
		$sumSquared = [0, 0, 0];    // rgb
		foreach ($colors as $color) {
			$sumSquared[0] += $color[0] * $color[0];
			$sumSquared[1] += $color[1] * $color[1];
			$sumSquared[2] += $color[2] * $color[2];
		}
		$amount = sizeof($colors);
		return [
			intval(sqrt($sumSquared[0] / $amount)),
			intval(sqrt($sumSquared[1] / $amount)),
			intval(sqrt($sumSquared[2] / $amount))];
	}

}
