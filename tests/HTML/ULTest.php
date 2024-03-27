<?php

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 29.01.2016
 * Time: 11:30
 */
class ULTest extends PHPUnit\Framework\TestCase
{

	/**
	 * @var UL
	 */
	protected $ul;

	public function setUp(): void
	{
		$this->ul = new UL([
			'slawa' => 'Slawa',
			'marina' => 'Marina',
		]);
	}

	public function test_noLink()
	{
		$render = $this->ul->render();
		$this->assertContains('<li class="active">Slawa</li>', $render);
	}

	public function test_linkWrap()
	{
		$this->ul->makeClickable();
		$render = $this->ul->render();
		$this->assertContains('<li class="active"><a href="slawa">Slawa</a></li>', $render);
	}

	public function test_linkFunc()
	{
		$this->ul->linkFunc = function ($class, $name) {
			return "$class=>$name";
		};
		$this->ul->linkWrap = '<link>###LINK###</link><text>|</text>';
		$render = $this->ul->render();
		$this->assertContains('<li class="active"><link>slawa=>Slawa</link><text>Slawa</text>', $render);
	}

}
