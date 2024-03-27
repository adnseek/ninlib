<?php

class MockIndex
{

	/** @var MockController */
	public $controller;

	/** @var Config */
	public $config;

	public $bodyClasses = [];

	public $withSidebar = true;

	public function __construct(MockController $c, Config $config)
	{
		$this->controller = $c;
		$this->config = $config;
	}

	public function addCSS($path)
	{
	}

	public function implodeCSS()
	{
		return null;
	}

	public function implodeJS()
	{
		return null;
	}

	public function getBookmarks()
	{
		return [];
	}

}
