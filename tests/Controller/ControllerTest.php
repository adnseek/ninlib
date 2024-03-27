<?php
/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 27.04.2017
 * Time: 16:55
 */

namespace nadlib\Controller;

use AppController4Test;
use nadlib\Test\MockRequest;
use PHPUnit\Framework\TestCase;

class ControllerTest extends TestCase
{

	protected $location = 'http://mock.request.tld';
	protected $globalPrefix = '/some/folder';

	/**
	 * @var MockRequest
	 */
	protected $request;

	public function setUp(): void
	{
		self::markTestSkipped('PG dependent');
		$this->request = new MockRequest();
	}

	public function test_getLocation()
	{
		$byRequest = $this->request->getDocumentRootByRequest();
		$this->assertEquals('/', $byRequest);

		$byDocRoot = $this->request->getDocumentRootByDocRoot();
		$this->assertEquals(null, $byDocRoot);

		$docRoot = $this->request->getDocumentRoot();
		$this->assertEquals('/', $docRoot);

		$location = $this->request->getLocation();
		$this->assertEquals($this->location . $this->globalPrefix . '/', $location . '');
	}

	public function test_makeLinkSimple()
	{
		$c = new AppController4Test();
		$c->linker->useRouter = false;
		$c->request = $this->request;
		$link = $c->makeURL(['a' => 'b']);
		$link->setHost(null);
		$this->assertEquals($this->globalPrefix . '/?a=b', $link . '');
	}

	public function test_makeLinkSimpleWithPrefix()
	{
		$c = new AppController4Test();
		$c->linker->useRouter = false;
		$c->request = $this->request;
		$link = $c->makeURL(['a' => 'b'], 'prefix');
		$link->setHost(null);
		$this->assertEquals($this->globalPrefix . '/prefix?a=b', $link . '');
	}

	public function test_makeLinkRouter()
	{
		$c = new AppController4Test();
		$c->linker->useRouter = true;
		$c->request = $this->request;
		$link = $c->makeURL(['a' => 'b'], 'prefix');
		$link->setHost(null);
		$this->assertEquals($this->globalPrefix . '/prefix?a=b', $link . '');
	}

	public function test_makeLinkRouterWithPrefix()
	{
		$c = new AppController4Test();
		$c->linker->useRouter = true;
		$c->request = $this->request;
		$link = $c->makeURL(['a' => 'b'], 'prefix');
		$link->setHost(null);
		$this->assertEquals($this->globalPrefix . '/prefix?a=b', $link . '');
	}

	public function test_makeLinkCSimple()
	{
		$c = new AppController4Test();
		$c->linker->useRouter = false;
		$c->request = $this->request;
		$link = $c->makeURL(['a' => 'b', 'c' => 'Controller']);
		$link->setHost(null);
		$this->assertEquals($this->globalPrefix . '/?a=b&c=Controller', $link . '');
	}

	public function test_makeLinkCRouter()
	{
		$c = new AppController4Test();
		$c->linker->useRouter = true;
		$c->request = $this->request;
		$link = $c->makeURL(['a' => 'b', 'c' => 'Controller']);
		$link->setHost(null);
		$this->assertEquals($this->globalPrefix . '/Controller?a=b', $link . '');
	}

}
