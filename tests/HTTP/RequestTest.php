<?php
/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 19.12.13
 * Time: 15:03
 */

class RequestTest extends PHPUnit\Framework\TestCase
{

	/**
	 * @var Request $r
	 */
	protected $r;

	public function setUp(): void
	{
		$this->r = Request::getInstance();
	}

	public function test_set()
	{
		$this->r->set('a', 'b');
		$this->assertEquals('b', $this->r->getTrim('a'));
	}

	public function test_unset()
	{
		//debug($this->r);
		$this->r->set('a', 'b');
		$this->r->un_set('a');
		$this->assertEmpty($this->r->getTrim('a'));
	}

	public function test_getTrim()
	{
		$this->r->set('a', ' some words' . "\n\t");
		$this->assertEquals('some words', $this->r->getTrim('a'));
	}

	/**
	 * @expectedException Exception
	 */
	public function test_getTrimRequired()
	{
		$this->r->set('a', '  ');
		$this->r->getTrimRequired('a');
	}

	/**
	 * @expectedException Exception
	 */
	public function test_getOneOf()
	{
		$this->r->set('a', 'b');
		$this->r->getOneOf('a', ['c']);
	}

	public function test_getInt()
	{
		$this->r->set('i', '10');
		$this->assertEquals(10, $this->r->getInt('i'));
	}

	public function test_getInt0()
	{
		$this->r->set('i', '10');
		$this->assertEquals(0, $this->r->getInt('new'));
	}

	public function test_getIntOrNULL()
	{
		$this->assertNull($this->r->getIntOrNULL('new'));
	}

	public function test_getIntIn()
	{
		$this->r->set('i', 10);
		$this->assertEquals(10, $this->r->getIntIn('i', [
			9 => '',
			10 => '',
			11 => '',
		]));
	}

	public function test_getIntIn0()
	{
		$this->r->set('i', 10);
		$this->assertNull($this->r->getIntIn('i', [
			9 => '',
			11 => '',
		]));
	}

	public function test_getLocation()
	{
		$_SERVER['DOCUMENT_ROOT'] = 'Z:/web/dev-jobz/htdocs/';
		$_SERVER['HTTP_HOST'] = 'dev-jobz.local';
		$location = Request::getLocation();
//		debug($location . '');
		$this->assertEquals('http://' . gethostname() . '/', $location);
	}

	public function test_dir_of_file()
	{
		$set = [
			'a' => '.',
			'/a' => DIRECTORY_SEPARATOR,  // only case where Windows matter
			'/a/b' => '/a',
			'/a/b/c' => '/a/b',
			'/a/b/c/' => '/a/b/c',
		];
		foreach ($set as $check => $must) {
			$this->assertEquals($must, Request::dir_of_file($check));
		}
	}

	public function test_getDocumentRootByIsDir()
	{
		$result = Request::getDocumentRootByIsDir();
		$this->assertEquals('/', $result);
	}

	public function test_getOnlyHost()
	{
		$host = Request::getOnlyHost();
		$this->assertEquals(gethostname(), $host);
	}

	public function test_isAjax()
	{
		$r = Request::getInstance();
		$r->set('ajax', false);
		$this->assertFalse($r->isAjax());
		$r->set('ajax', true);
		$this->assertTrue($r->isAjax());
		$r->set('ajax', false);
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
		$this->assertTrue($r->isAjax());
	}

}
