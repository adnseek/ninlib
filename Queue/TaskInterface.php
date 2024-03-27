<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Majid (Pedram) Jokar
 * Date: 01.10.13
 * Time: 17:12
 * To change this template use File | Settings | File Templates.
 */

interface TaskInterface
{

	/**
	 * Based on the $data it's doing something.
	 * Return value is only displayed on the command live.
	 * To indicate a failure - throw Exception
	 * @param array $data
	 * @return bool - success or not?
	 */
	public function process(array $data);

}
