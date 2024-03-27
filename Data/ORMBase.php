<?php

class ORMBase
{

	public function __construct($media)
	{
		$base = null;
		if (is_object($media)) {
			$base = get_object_vars($media);
		} elseif (is_array($media)) {
			$base = $media;
		} else {
			//debug($media);
		}

		if ($base) {
			foreach ($base as $key => $value) {
				$this->$key = $value;
			}
		}
	}

}
