<?php
/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 10.10.2018
 * Time: 11:14
 */


class DBLayerTest extends PHPUnit\Framework\TestCase
{

	public function testQuoteKey()
	{
		$db = new DBLayer();
		$quoted = $db->quoteKey('login');
		$this->assertEquals('"login"', $quoted);
		$function = 'trim(login)';
		$unquoted = $db->quoteKey($function);
		$this->assertEquals($function, $unquoted);
	}
}
