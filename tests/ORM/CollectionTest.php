<?php

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 02.02.2016
 * Time: 16:42
 */
class CollectionTest extends NadlibTestCase
{

	public function test_lazyMemeberIterator()
	{
		$this->markTestSkipped(
			'RequestCollection was not found.'
		);

		$rc = new RequestCollection();
		$rc->orderBy = 'ORDER BY ctime DESC LIMIT 10';
		$iterator = $rc->getLazyMemberIterator('ORSRequest');
		$current = $iterator->current();
		debug($current);
	}

	/**
	 * @throws DatabaseException
	 * @throws MustBeStringException
	 * @throws Exception
	 */
	public function test_immutability_with_count()
	{
		$db = new DBPlacebo();
		$db->setQB(new SQLBuilder($db));
		$c = new Collection(null, [], '', $db);
		$query100 = $c->getQueryWithLimit();
		$this->assertEqualsIngnoreSpaces('SELECT
"Collection".*
FROM "Collection"
 WHERE
ORDER BY id', $query100 . '');
	}

}
