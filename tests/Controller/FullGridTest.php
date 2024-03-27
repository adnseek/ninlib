<?php

namespace nadlib\Controller;

use Config;
use DBPlacebo;
use FullGrid4Test;
use PHPUnit\Framework\TestCase;

class FullGridTest extends TestCase
{

	public function setUp(): void
	{
		self::markTestSkipped('PG dependent');
		$config = Config::getInstance();
		$config->setDB(new DBPlacebo());
	}

	public function testGetColumnsForm()
	{
		$fg = new FullGrid4Test();
		$fg->postInit();
		$fg->getColumnsForm();
		$this->assertTrue(true);  // not crashed
	}

}
