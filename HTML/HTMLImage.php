<?php

class HTMLImage extends HTMLTag
{

	/**
	 * @var string
	 */
	public $filename;

	public function __construct($filename, array $attr = [])
	{
		parent::__construct('img', $attr);
		$this->filename = $filename;
	}

	public function getTag()
	{
		return $this->render();
	}

	public function render()
	{
		$this->setAttr('src', $this->getImageLink());
		//debug($this->attr);
		return parent::render();
	}

	public function getImageLink()
	{
		if ($this->isLocalFile()) {
			if ($this->filename[0] == '/') {
				$documentRoot = AutoLoad::getInstance()->getAppRoot();
				$documentRoot = str_replace('\\', '/', $documentRoot);
				$realpath = realpath($this->filename);
				$realpath = str_replace('\\', '/', $realpath);
				$fileLink = str_replace($documentRoot, '', $realpath);
				//debug($documentRoot, $realpath, $fileLink);
			} else {
				$fileLink = $this->filename;
			}
		} else {
			$fileLink = $this->filename;
		}
		return $fileLink;
	}

	public function isLocalFile()
	{
		return !contains($this->filename, '://');
	}

	public function getBaseName()
	{
		return basename($this->filename);
	}

	public function getExif()
	{
		$exif = null;
		if (file_exists($this->filename)
			&& str_endsWith($this->filename, '.jpeg')
			&& function_exists('exif_read_data')
		) {
			$exif = exif_read_data($this->filename); // warning if PNG
		}
		return $exif;
	}

	public function getLatLon()
	{
		$lat = $lon = null;
		$exif = $this->getExif();
		if ($exif && $exif["GPSLatitude"]) {
			$lat = $this->getGps($exif["GPSLatitude"], $exif['GPSLatitudeRef']);
			$lon = $this->getGps($exif["GPSLongitude"], $exif['GPSLongitudeRef']);
		}
		return [$lat, $lon];
	}

	public function getGps($exifCoord, $hemi)
	{
		$degrees = count($exifCoord) > 0 ? $this->gps2Num($exifCoord[0]) : 0;
		$minutes = count($exifCoord) > 1 ? $this->gps2Num($exifCoord[1]) : 0;
		$seconds = count($exifCoord) > 2 ? $this->gps2Num($exifCoord[2]) : 0;

		$flip = ($hemi == 'W' or $hemi == 'S') ? -1 : 1;

		return $flip * ($degrees + $minutes / 60 + $seconds / 3600);
	}

	public function gps2Num($coordPart)
	{
		$parts = explode('/', $coordPart);

		if (count($parts) <= 0)
			return 0;

		if (count($parts) == 1)
			return $parts[0];

		return floatval($parts[0]) / floatval($parts[1]);
	}

	public function hasLatLon()
	{
		list($lat, $lon) = $this->getLatLon();
		return $lat || $lon;
	}

	public function getMiniMap()
	{
		return '<div class="MiniMap">
			<img src="" />
		</div>';
	}

	public function exists()
	{
		return file_exists($this->filename);
	}

	public function getBaseNameWithCorrectExtension()
	{
		$basename = $this->getBaseName();
		$mimeExt = $this->getMimeExt();
		if ($mimeExt) {
			$oldExt = pathinfo($this->filename, PATHINFO_EXTENSION);
			$correct = str_replace('.' . $oldExt, '.' . $mimeExt, $basename);
		} else {
			$correct = $basename;
		}
		return $correct;
	}

	public function getMimeExt()
	{
		$newExt = null;
		$u = new Uploader();
		$mime = $u->get_mime_type($this->filename);
		//debug($mime);
		if ($mime) {
			$map = [
				'image/jpeg' => 'jpeg',
				'image/gif' => 'gif',
				'image/png' => 'png',
				'image/tiff' => 'tiff',
			];
			$newExt = ifsetor($map[$mime]);
		}
		return $newExt;
	}

	public function resize($width, $height = null)
	{
		if (!$height) {
			$height = $width;
		}
		$this->attr('width', $width);
		$this->attr('height', $height);
	}

}
