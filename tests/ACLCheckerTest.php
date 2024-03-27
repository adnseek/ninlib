<?php
/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 25.02.2016
 * Time: 19:07
 */

namespace nadlib\Test;

use ACLChecker;
use NOEUser;
use PHPUnit\Framework\TestCase;
use TestController;

class ACLCheckerTest extends TestCase
{

	public function test_compareACL()
	{
		$this->markTestSkipped(
			'AppController was not found.'
		);
		$controller = new TestController();
		$controller->user = null;
		$ac = new ACLChecker($controller);
		$okNothing = $ac->compareACL(null);
		$okNull = $ac->compareACL('null');
		$okUser = $ac->compareACL('user');
//		debug(typ($controller->user), $okNothing, $okNull, $okUser);
	}

	public function test_compareACLUser()
	{
		$this->markTestSkipped(
			'AppController was not found.'
		);
		$controller = new TestController();
		$controller->user = new NOEUser();
		$controller->user->id = 1;
		$ac = new ACLChecker($controller);
		$okNothing = $ac->compareACL(null);
		$okNull = $ac->compareACL('null');
		$okUser = $ac->compareACL('user');
//		debug(typ($controller->user), $okNothing, $okNull, $okUser);
	}

}
