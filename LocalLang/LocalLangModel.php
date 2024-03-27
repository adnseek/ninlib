<?php

class LocalLangModel extends OODBase
{
	public $table = 'app_interface';
	public $idField = 'id';

	public function getValue()
	{
		return $this->data['text'];
	}

}
