<?php

use spidgorny\nadlib\HTTP\URL;

/**
 * Created by PhpStorm.
 * User: Slawa
 * Date: 2016-01-24
 * Time: 23:17
 */
class URLTest extends PHPUnit\Framework\TestCase
{

	public function test_resolve_append()
	{
		$url = new URL('http://www.thueringer-wald.com/urlaub-wandern-winter/trusetaler-wasserfall-104618.html');
		$abs = $url->resolve('image.png');
//		debug($url->log);
		$this->assertEquals('http://www.thueringer-wald.com/urlaub-wandern-winter/image.png', $abs);
	}

	public function test_resolve_parent()
	{
		$url = new URL('http://www.thueringer-wald.com/urlaub-wandern-winter/trusetaler-wasserfall-104618.html');
		$abs = $url->resolve('../image.png');
//		debug($url->log);
		$this->assertEquals('http://www.thueringer-wald.com/image.png', $abs);
	}

	public function test_resolve_root()
	{
		$url = new URL('http://www.thueringer-wald.com/urlaub-wandern-winter/trusetaler-wasserfall-104618.html');
		$abs = $url->resolve('/image.png');
//		debug($url->log);
		$this->assertEquals('http://www.thueringer-wald.com/image.png', $abs);
	}

	public function test_absolute_constructor()
	{
		$original = 'http://www.thueringer-wald.com/urlaub-wandern-winter/trusetaler-wasserfall-104618.html';
		$url = new URL($original);
		$this->assertEquals($original, $url . '');
	}

	public function test_absolute_constructor_setDocumentRoot()
	{
		$original = 'https://ors.nintendo.de/slawa/SoftwareGrid/VersionGrid/VersionInfo?id=1966562';
		$url = new URL($original);
		$url->setDocumentRoot('/slawa/');
//		debug($url, $url.'');
		$this->assertEquals($original, $url . '');
	}

	public function test_replaceController()
	{
		$this->markTestSkipped();
		$url = new URL('http://localhost/level1/level2/level3');
		$url->replaceController('Class2');
		$this->assertEquals('http://localhost/Class2', $url . '');

		$url = new URL('http://localhost/docroot/level1/level2/level3');
//		debug($url->documentRoot);
		$url->documentRoot = '/docroot';
		$url->replaceController('Class2');
		$this->assertEquals('http://localhost/docroot/Class2', $url . '');
	}

}
