<?php
/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 06.12.2018
 * Time: 17:00
 */

namespace nadlib\User;

use PHPUnit\Framework\TestCase;
use Preferences;

class PreferencesTest extends TestCase
{

	public function testGet()
	{
		$user = (object)[
			'data' => [
				'prefs' => '',
			]
		];
		$p = new Preferences($user);
		$is123 = $p->get('not existing', 123);
		$this->assertEquals(123, $is123);
	}
}
