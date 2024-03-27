<?php

class AppController4Test extends Controller
{

	public function __construct()
	{
		$this->config = TestConfig::getInstance();
		parent::__construct();
	}

}
