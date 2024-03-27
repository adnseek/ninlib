<?php

/**
 * Created by PhpStorm.
 * User: Slawa
 * Date: 2017-03-22
 * Time: 16:26
 */
class HTMLFormTableTest extends PHPUnit\Framework\TestCase
{

	public function test_fillValues()
	{
		$f = new HTMLFormTable([
			'name' => 'Name',
			'email' => 'E-mail',
		]);
		$fixture = [
			'name' => 'slawa',
			'email' => 'someshit',
			'new_field' => 'new_values',
		];
		$f->fill($fixture);
		$values = $f->getValues();
//		debug($values);
		unset($fixture['new_field']);
		$this->assertEquals($fixture, $values);
	}

	public function test_fillValues_with_force()
	{
		$f = new HTMLFormTable([
			'name' => 'Name',
			'email' => 'E-mail',
		]);
		$fixture = [
			'name' => 'slawa',
			'email' => 'someshit',
			'new_field' => 'new_values',
		];
		$f->fill($fixture, true);
		$values = $f->getValues();
		unset($fixture['new_field']);
		$this->assertEquals($fixture, $values);
	}

	public function test_fillValues_twice()
	{
		$f = new HTMLFormTable([
			'name' => 'Name',
			'email' => 'E-mail',
		]);
		$fixture = [
			'name' => 'slawa',
			'email' => 'someshit',
		];
		$f->fill($fixture, true);
		$fixture = [
			'name' => 'slawa 2',
			'email' => 'someshit ',
		];
		$f->fill($fixture, true);
		$values = $f->getValues();
		$this->assertEquals($fixture, $values);
	}

	public function test_htmlspecialchars()
	{
		$f = new HTMLFormTable([
			'field' => [
				'value' => 'asd'
			]
		]);
		$f->showForm();
		$html = $f->getContent();
//		echo $html;
		$this->assertContains('value="asd"', $html);

		$f = new HTMLFormTable([
			'field' => [
				'value' => 'asd & "qwe"'
			]
		]);
		$f->showForm();
		$html = $f->getContent();
//		echo $html;
		$this->assertContains('value="asd &amp; &quot;qwe&quot;"', $html);
	}

}
