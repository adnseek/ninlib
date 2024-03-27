<?php

class UploadedFile
{

	public $name;
	public $type;
	public $tmp_name;
	public $error;
	public $size;
	public $mime;

	public function __construct(array $data)
	{
		foreach ($data as $key => $val) {
			$this->$key = $val;
		}
		$u = new MIME();
		$this->mime = $u->get_mime_type($this->tmp_name);
	}

}
