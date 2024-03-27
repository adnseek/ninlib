<?php

/**
 * Created by PhpStorm.
 * User: Slawa
 * Date: 2016-01-23
 * Time: 01:10
 */
class PathTest extends PHPUnit\Framework\TestCase
{

	public function test_Windows()
	{
		//$p = new Path(getcwd());
		$p = new Path("C:\\folderone\\two\\three");

		//debug($p->aPath, $p->sPath);
		$this->assertGreaterThan(3, sizeof($p->aPath));
		$this->assertEquals('C:', $p->aPath[0]);
		$this->assertStringStartsWith('C:/', $p . '');
	}

	public function test_cap_Windows()
	{
		$source = cap(getcwd());
		$p = new Path($source);
//		debug($p->aPath, $p->sPath);
		// all windows slash except last
		$source = str_replace('\\', '/', getcwd()) . '/';
		$this->assertEquals($source, $p->implode());
	}

	public function test_isAbsolute()
	{
		$path = new Path('asd/qwe');
		$this->assertFalse($path->isAbsolute());
		$path = new Path('dev-jobz/Topic/hyperledger');
		$this->assertFalse($path->isAbsolute());
	}

	public function test_appRoot()
	{
		$al = AutoLoad::getInstance();
		$appRoot = $al->getAppRoot();
//		debug($appRoot . '');
//		$this->assertEquals(dirname(dirname(dirname(__FILE__))), $appRoot.'');
		$this->markTestSkipped();
	}

	public function test_relativeFromAppRoot()
	{
		$this->markTestSkipped(
			'Cannot work from nadlib as a standalone.'
		);

		//$source = 'components/jquery/jquery.js?1453328048';
		$source = 'components/bootstrap/less/bootstrap.js?1453328048';
		$path = new Path($source);
		$relative = $path->relativeFromAppRoot();
		//debug($relative.'');
		$this->assertContains($relative . '', [
			'nadlib/' . $source,
			'vendor/spidgorny/nadlib/' . $source,
			'Users/DEPIDSVY/nadlib/' . $source,
			'tests/HTTP/' . $source,
		]);
	}

	public function test_append()
	{
		$path = new Path('/var/www/htdocs/');
		$path->append(new Path('ftp'));
		$this->assertEquals('/var/www/htdocs/ftp', $path . '');
	}

	public function test_append_capped()
	{
		$path = new Path('/var/www/htdocs/');
		$path->append(new Path('ftp/'));
		$this->assertEquals('/var/www/htdocs/ftp/', $path . '');
	}

	public function test_back_path()
	{
		$path = new Path('../ftp');
		$this->assertEquals(['..', 'ftp'], $path->aPath);
	}

	public function test_append_back()
	{
		$path = new Path('/var/www/htdocs/');
		$path->append(new Path('../ftp'));
		$this->assertEquals('/var/www/ftp', $path . '');
	}

	public function test_append_back_twice()
	{
		$path = new Path('/var/www/htdocs/');
		$path->append(new Path('../../ftp'));
		$this->assertEquals('/var/ftp', $path . '');
	}

	public function test_remove()
	{
		$path = new Path('/var/www/htdocs/');
		$path->remove('/var/www');
		$this->assertEquals('/htdocs/', $path . '');
	}

	public function test_setFile()
	{
		$path = new Path('xxx');
		$path->setFile('yyy');
		$this->assertEquals('yyy', $path . '');
	}

	public function test_setFile_empty()
	{
		$path = new Path('xxx');
		$path->setFile('');
		$this->assertEquals('', $path . '');
	}

}
