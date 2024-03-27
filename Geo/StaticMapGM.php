<?php

/**
 * Created by PhpStorm.
 * User: Slawa
 * Date: 2017-03-22
 * Time: 17:38
 *
 * Warning: This service requires an API key.
 */
class StaticMapGM
{

	var $location;

	var $lat;

	var $lon;

	var $size = '640x480';

	var $path;

	var $zoom;

	function __construct($location = '', $lat = null, $lon = null)
	{
		$this->location = $location;
		$this->lat = $lat;
		$this->lon = $lon;
	}

	function render()
	{
		$params = [
			'size' => $this->size,
			'maptype' => 'roadmap',
			//			'markers' => 'color:blue%7Clabel:S%7C40.702147,-74.015794&markers=color:green%7Clabel:G%7C40.711614,-74.012318%20&markers=color:red%7Clabel:C%7C40.718217,-73.998284',
			'sensor' => 'false',
		];
		if ($this->path) {
			$params['path'] = $this->path;
		} elseif ($this->lat && $this->lon) {
			$params['center'] = $this->lat . ',' . $this->lon;
			$params['zoom'] = $this->zoom;
		} else {
			$params['center'] = $this->location;
		}
		$src = 'https://maps.googleapis.com/maps/api/staticmap?' . http_build_query($params);
		$html = new HTML();
		list($width, $height) = trimExplode('x', $this->size);
		$img = $html->img($src, [
			'width' => $width,
			'height' => $height,
		]);
		return $img;
	}

}
