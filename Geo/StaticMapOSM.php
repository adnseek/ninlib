<?php

/**
 * Created by PhpStorm.
 * User: Slawa
 * Date: 2017-03-22
 * Time: 17:38
 */
class StaticMapOSM
{

	public $location;

	public $lat;

	public $lon;

	public $size = '640x480';

	public function __construct($location, $lat = null, $lon = null)
	{
		$this->location = $location;
		$this->lat = $lat;
		$this->lon = $lon;
	}

	public function render()
	{
		list($width, $height) = explode('x', $this->size);
		if ($this->lat && $this->lon) {
			$content = '<figure>
			<img src="' . $this->getImagePath() . '" width="' . $width . '" height="' . $height . '"/>
			<figcaption>' . $this->location . '</figcaption>
		</figure>';
		} else {
			$content = '<figure>
			<img src="' . $this->getImagePath() . '" />
			<figcaption>' . $this->location . '</figcaption>
		</figure>';
		}
		return $content;
	}

	public function getImagePath()
	{
		if ($this->lat && $this->lon) {
			return 'http://staticmap.openstreetmap.de/staticmap.php?center=' . $this->lat . ',' . $this->lon . '&zoom=11&size=' . $this->size;
		} else {
			return 'http://staticmap.openstreetmap.de/staticmap.php?center=' . $this->location . '&zoom=11&size=' . $this->size;
		}
	}

}
