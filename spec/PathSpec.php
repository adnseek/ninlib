<?php

namespace spec;

use Path as Path;
use PhpSpec\ObjectBehavior;

/**
 * @mixin Path
 */
class PathSpec extends ObjectBehavior
{

	public function __construct()
	{
		require_once __DIR__ . '/../init.php';
		require_once __DIR__ . '/../HTTP/Path.php';
		require_once __DIR__ . '/../HTTP/Request.php';
	}

	public function it_is_initializable()
	{
		$this->beConstructedWith(new Path(getcwd()));
		$this->shouldHaveType(Path::class);
	}

	public function it_normalizes_home_page()
	{
		define('DEVELOPMENT', true);
		$this->beConstructedWith(new Path('/~depidsvy/floorplan/details'));
		$this->normalizeHomePage();
		$this->__toString()->shouldBe('depidsvy/public_html/floorplan/details');
	}

	public function it_normalizes_home_page_2()
	{
		define('DEVELOPMENT', true);
		$this->beConstructedWith('/srv/www/htdocs/~depidsvy/floorplan/details/AboutAccessRights');
		$this->normalizeHomePage();
		$this->__toString()->shouldBe('depidsvy/public_html/floorplan/details');
	}

}
