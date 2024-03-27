<?php

class MIME
{
	/**
	 * Which method of mime detection was used
	 * @var string
	 */
	public $mimeMethod;

	function test_mime()
	{
		return [
			'finfo' => class_exists('finfo'),
			'finfo_open' => function_exists('finfo_open'),
			'mime_content_type' => function_exists('mime_content_type'),
		];
	}

	/**
	 * Tries different methods
	 * @param $filename
	 * @return string
	 */
	function get_mime_type($filename)
	{
		if (class_exists('finfo')) {
			$fi = new finfo();
			$mime = $fi->file($filename, FILEINFO_MIME_TYPE);
			$this->mimeMethod = 'finfo';
		} elseif (function_exists('finfo_open')) {
			$fi = finfo_open(FILEINFO_MIME_TYPE);
			$mime = finfo_file($fi, $filename);
			$this->mimeMethod = 'finfo_open';
		} elseif (function_exists('mime_content_type')) {
			$mime = mime_content_type($filename);
			$this->mimeMethod = 'mime_content_type';
		} else {
			$mime = $this->get_mime_type_system($filename);
			$this->mimeMethod = 'get_mime_type_system';
		}

		if (!$mime) {
			$mime = $this->mime_by_ext($filename);
			$this->mimeMethod = 'mime_by_ext';
		}

		$mime = trim($mime);    // necessary !!!
		//debug($mime, $this->mimeMethod);
		return $mime;
	}

	/**
	 * http://www.php.net/manual/en/function.finfo-open.php#78927
	 * @param $filepath
	 * @return string
	 */
	protected function get_mime_type_system($filepath)
	{
		ob_start();
		system("file --mime-type -i --mime -b {$filepath}");
		$output = ob_get_clean();
		$output = explode("; ", $output);    // text/plain; charset=us-ascii
		if (is_array($output)) {
			$output = $output[0];
		}
		$output = explode(" ", $output);    // text/plain charset=us-ascii
		if (is_array($output)) {
			$output = $output[0];
		}
		return $output;
	}

	/**
	 * http://nl3.php.net/manual/en/function.mime-content-type.php#85879
	 * @param $filename
	 * @return mixed|string
	 */
	public function mime_by_ext($filename)
	{
		$mime_types = [
			'txt' => 'text/plain',
			'htm' => 'text/html',
			'html' => 'text/html',
			'php' => 'text/html',
			'css' => 'text/css',
			'js' => 'application/javascript',
			'json' => 'application/json',
			'xml' => 'application/xml',
			'swf' => 'application/x-shockwave-flash',
			'flv' => 'video/x-flv',

			// images
			'png' => 'image/png',
			'jpe' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'jpg' => 'image/jpeg',
			'gif' => 'image/gif',
			'bmp' => 'image/bmp',
			'ico' => 'image/vnd.microsoft.icon',
			'tiff' => 'image/tiff',
			'tif' => 'image/tiff',
			'svg' => 'image/svg+xml',
			'svgz' => 'image/svg+xml',

			// archives
			'zip' => 'application/zip',
			'rar' => 'application/x-rar-compressed',
			'exe' => 'application/x-msdownload',
			'msi' => 'application/x-msdownload',
			'cab' => 'application/vnd.ms-cab-compressed',

			// audio/video
			'mp3' => 'audio/mpeg',
			'qt' => 'video/quicktime',
			'mov' => 'video/quicktime',

			// adobe
			'pdf' => 'application/pdf',
			'psd' => 'image/vnd.adobe.photoshop',
			'ai' => 'application/postscript',
			'eps' => 'application/postscript',
			'ps' => 'application/postscript',

			// ms office
			'doc' => 'application/msword',
			'rtf' => 'application/rtf',
			'xls' => 'application/vnd.ms-excel',
			'ppt' => 'application/vnd.ms-powerpoint',

			// open office
			'odt' => 'application/vnd.oasis.opendocument.text',
			'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
		];

		$ext = strtolower(last(explode('.', $filename)));
		if (array_key_exists($ext, $mime_types)) {
			return $mime_types[$ext];
		} else {
			return 'application/octet-stream';
		}
	}

}
