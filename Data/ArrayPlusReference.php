<?php

$tmp = error_reporting(error_reporting() ^ E_STRICT);

/**
 * Class ArrayPlusReference
 * @mixin ArrayPlus
 */
class ArrayPlusReference /*extends ArrayPlus */
{

	public function __construct(array &$a = [])
	{
		$this->setData($a);
	}

	public static function create(array &$data = [])
	{
		$self = new self($data);
		return $self;
	}

	/**
	 * @return array
	 */
	public function &getData()
	{
		return (array)$this;
	}

}

error_reporting($tmp);

function APR(array &$a = [])
{
	return ArrayPlusReference::create($a);
}
