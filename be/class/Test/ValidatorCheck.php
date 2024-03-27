<?php

class ValidatorCheck extends AppControllerBE
{

	public function render()
	{
		$f = new HTMLFormTable([
			'obligatory' => [
				'label' => 'Obligatory',
				'validate' => 'obligatory',
			],
			'mustBset' => [
				'label' => 'Must be set',
				'optional' => true,
				'validate' => 'mustBset',
			],
			'email' => [
				'label' => 'Email',
				'optional' => true,
				'validate' => 'email',
			],
			'password' => [
				'label' => 'Password',
				'optional' => true,
				'validate' => 'password',
			],
			'min' => [
				'label' => 'Min 10',
				'optional' => true,
				'min' => '10',
			],
			'max' => [
				'label' => 'Max 10',
				'optional' => true,
				'max' => '10',
			],
			'minlen' => [
				'label' => 'Minlen 10',
				'optional' => true,
				'minlen' => '10',
			],
			'maxlen' => [
				'label' => 'Maxlen 10',
				'optional' => true,
				'maxlen' => '10',
			],
			'int' => [
				'label' => 'Int',
				'optional' => true,
				'validate' => 'int',
			],
			'date' => [
				'label' => 'Date',
				'optional' => true,
				'validate' => 'date',
			],
		]);
		$f->fill($_REQUEST);
		unset($f->desc['mustBset']['value']);
		$f->validate();
		$f->showForm();
		$f->submit();
		$content = $f;
		return $content;
	}

}
