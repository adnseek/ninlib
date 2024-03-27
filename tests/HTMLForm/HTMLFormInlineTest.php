<?php
/**
 * Created by PhpStorm.
 * User: depidsvy
 * Date: 14.06.2018
 * Time: 15:17
 */

namespace nadlib\HTMLForm;

use HTMLFormInline;
use PHPUnit\Framework\TestCase;

class HTMLFormInlineTest extends TestCase
{

	public function testSimple()
	{
		$f = new HTMLFormInline([
			'name' => [
				'label' => 'Name',
			]
		]);
		$html = $f->showForm();
//		debug($html->getContent());
		$this->assertEquals($this->normalize('<form method="POST">
<div class="form-group">
<label>
<span>Name</span>
<input type="text" class="text form-control" name="name" id="id-name" required="required" />
</label>
</div>
</form>
'), $this->normalize($html->getContent()));
	}

	public function normalize($string)
	{
//	echo chunk_split(bin2hex($string), 32);
//	echo PHP_EOL;
		$ok = str_replace("\n", "\r\n", str_replace("\r", '', $string));  // working
//	$ok = preg_replace("~\r\n?~u", PHP_EOL, $string);	// not working
//	$ok = preg_replace("~\R~u", PHP_EOL, $string);		// working
//	echo chunk_split(bin2hex($ok), 32);
//	echo PHP_EOL;
		return $ok;
	}

}
