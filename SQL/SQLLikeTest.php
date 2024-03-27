<?php


class SQLLikeTest extends PHPUnit\Framework\TestCase
{

	public function testDebug()
	{
		ob_start();
		debug('asd');
		$this->assertNotEmpty(ob_get_clean());
	}

	public function testGetParameter()
	{
		$like = new SQLLike('asd');
		$like->injectDB(new DBPlacebo());
		$like->injectField('field');
		$sql = $like->__toString();
		$this->assertEquals("\"field\" ILIKE '' || ? || ''", $sql);
		$param = $like->getParameter();
		$this->assertEquals('asd', $param);
	}

	public function testGetParameterInsideWhere()
	{
		$like = new SQLLike('asd');
		$where = new SQLWhere();
		$where->add($like, 'field');
		$where->injectDB(new DBPlacebo());
		$sql = $where->__toString();
		$sql = $this->normalize($sql);
		$this->assertEquals(" WHERE \"field\" ILIKE '' || ? || ''", $sql);
		$params = $where->getParameters();
		//debug($where->getAsArray(), $params);
		$this->assertEquals(['asd'], $params);
	}

	public function normalize($sql)
	{
		$sql = str_replace("\n", ' ', $sql);
		$sql = preg_replace('/\s+/', ' ', $sql);
		return $sql;
	}

}
