<?php

class PGArray extends AsIs
{

	/**
	 * @var array
	 */
	public $data;
	/**
	 * @var DBLayer
	 */
	protected $db;
	/**
	 * @var bool
	 */
	protected $standard_conforming_strings;

	public function __construct(DBInterface $db, array $data = null)
	{
		$this->db = $db;

		$query = "SHOW standard_conforming_strings;";
		$result = $this->db->perform($query);
		$return = pg_fetch_assoc($result);
		pg_free_result($result);
		$this->standard_conforming_strings = first($return);
		$this->standard_conforming_strings =
			strtolower($this->standard_conforming_strings) == 'on';

		if ($data) {
			$this->data = $data;
		}
	}

	public function set(array $data)
	{
		$this->data = $data;
	}

	public function __sleep()
	{
		$props = get_object_vars($this);
		unset($props['db']);
		$props = array_keys($props);
		return array_keys($props);
	}

	/**
	 * New better syntax for using it in SQL which does not
	 * require tripple escaping of backslashes
	 * @return string
	 * @throws MustBeStringException
	 */
	public function __toString()
	{
		$quoted = $this->db->quoteValues($this->data);
		return 'ARRAY[' . implode(', ', $quoted) . ']';
	}

	public function encodeInString()
	{
		return $this->setPGArray($this->data);
	}

	/**
	 * @param array $data
	 * @param string $type "::integer[]"
	 * @return AsIs
	 */
	public function setPGArray(array $data, $type = '')
	{
		foreach ($data as &$el) {
			if (is_array($el)) {
				$el = $this->setPGArray($el);
			} else {
				$el = pg_escape_string($el);
//				$el = addslashes($el);

				if ($this->standard_conforming_strings) {
					//$el = addslashes($el); // changed after postgres version updated to 9.4
					//$el = str_replace('\\', '\\\\', $el);
					$el = str_replace("'", "''", $el);
					$el = "'" . $el . "'";
				} else {
					$el = str_replace('"', '\\"', $el);
					$el = '"' . $el . '"';
				}
			}
		}
		//$result = '{'.implode(',', $data).'}';
		$result = new AsIs('ARRAY[' . implode(',', $data) . ']' . $type);
		//debug($result.'', $this->standard_conforming_strings, $el, $data);
		return $result;
	}

	/**
	 * http://www.php.net/manual/en/ref.pgsql.php#57709
	 *
	 * @param string $pgArray
	 * @return array
	 */
	public function PGArrayToPHPArray($pgArray)
	{
		$ret = [];
		$stack = [&$ret];
		$pgArray = substr($pgArray, 1, -1);
		$pgElements = explode(",", $pgArray);

		//ArrayDump($pgElements);

		foreach ($pgElements as $elem) {
			if (substr($elem, -1) == "}") {
				$elem = substr($elem, 0, -1);
				$newSub = [];
				while (substr($elem, 0, 1) != "{") {
					$newSub[] = $elem;
					$elem = array_pop($ret);
				}
				$newSub[] = substr($elem, 1);
				$ret[] = array_reverse($newSub);
			} else {
				$ret[] = $elem;
			}
		}
		return $ret;
	}

	/**
	 * Slawa's own recursive approach. Not working 100%. See mTest from ORS.
	 * @param string $input
	 * @return array
	 * @internal param string $dbarr
	 */
	public function getPGArray($input)
	{
		$input = (string)$input;
		if (strlen($input) && $input[0] == '{') {    // array inside
			$input = substr(substr(trim($input), 1), 0, -1);    // cut { and }
			return $this->getPGArray($input);
		} else {
			if (strpos($input, '},{') !== false) {
				$parts = explode('},{', $input);
				foreach ($parts as &$p) {
					$p = $this->getPGArray($p);
				}
			} elseif (str_contains($input, '{')) {
				// JSON inside
				$jsonStart = strpos($input, '{');
				$jsonEnd = strpos($input, '}');
				$json = substr($input, $jsonStart, $jsonEnd - $jsonStart + 1);
				$input = substr($input, 0, $jsonStart) .
					'*!*JSON*!*' .
					substr($input, $jsonEnd + 1);
				$parts = $this->str_getcsv($input, ',', '"');
//				ini_set('xdebug.var_display_max_data', 9999);
//				debug($input, $parts, $json);
				foreach ($parts as &$p) {
					$p = str_replace('*!*JSON*!*', stripslashes($json), $p);
				}
			} else {
				$parts = $this->str_getcsv($input, ',', '"');
				$parts = (array)$parts;
				//debug($parts);
				//$parts = array_map('stripslashes', $parts);	// already done in str_getcsv
			}
			return $parts;
		}
	}

	/**
	 * Change a db array into a PHP array
	 * @param string $input
	 * @return A PHP array
	 * @internal param String $arr representing the DB array
	 */
	/*	function getPGArray($dbarr) {
			// Take off the first and last characters (the braces)
			$arr = substr($dbarr, 1, strlen($dbarr) - 2);

			// Pick out array entries by carefully parsing.  This is necessary in order
			// to cope with double quotes and commas, etc.
			$elements = array();
			$i = $j = 0;
			$in_quotes = false;
			while ($i < strlen($arr)) {
				// If current char is a double quote and it's not escaped, then
				// enter quoted bit
				$char = substr($arr, $i, 1);
				if ($char == '"' && ($i == 0 || substr($arr, $i - 1, 1) != '\\'))
					$in_quotes = !$in_quotes;
				elseif ($char == ',' && !$in_quotes) {
					// Add text so far to the array
					$elements[] = substr($arr, $j, $i - $j);
					$j = $i + 1;
				}
				$i++;
			}
			// Add final text to the array
			$elements[] = substr($arr, $j);

			// Do one further loop over the elements array to remote double quoting
			// and escaping of double quotes and backslashes
			for ($i = 0; $i < sizeof($elements); $i++) {
				$v = $elements[$i];
				if (strpos($v, '"') === 0) {
					$v = substr($v, 1, strlen($v) - 2);
					$v = str_replace('\\"', '"', $v);
					$v = str_replace('\\\\', '\\', $v);
					$elements[$i] = $v;
				}
			}

			return $elements;
		}
	*/
	public static function str_getcsv($input, $delimiter = ',', $enclosure = '"', $escape = '\\', $eol = null)
	{
		$temp = fopen("php://memory", "rw");
		fwrite($temp, $input);
		fseek($temp, 0);
		$r = [];
		while (($data = fgetcsv($temp, 4096, $delimiter, $enclosure, $escape)) !== false) {
//			$data = array_map('stripcslashes', $data);
			$data = array_map(function ($str) {
				// exactly opposite to setPGArray()
				$str = str_replace('\"', '"', $str);
				// this is needed because even with
				// $standard_conforming_strings = on
				// PostgreSQL is escaping backslashes
				// inside arrays (not in normal strings)
				// select 'a
				// b', ARRAY['slawa', '{"a":"multi\nline"}']
				$str = str_replace('\\\\', '\\', $str);
				return $str;
			}, $data);
			$r[] = $data;
		}
		fclose($temp);
		return ifsetor($r[0]);
	}

	/*	public function getPGArray($text) {
			$this->pg_array_parse($text, $output);
			return $output;
		}

		private function pg_array_parse( $text, &$output, $limit = false, $offset = 1 ) {
			if( false === $limit )
			{
				$limit = strlen( $text )-1;
				$output = array();
			}
			if( '{}' != $text )
				do
				{
					if( '{' != $text{$offset} )
					{
						preg_match( "/(\\{?\"([^\"\\\\]|\\\\.)*\"|[^,{}]+)+([,}]+)/", $text, $match, 0, $offset );
						$offset += strlen( $match[0] );
						$output[] = ( '"' != $match[1]{0} ? $match[1] : stripcslashes( substr( $match[1], 1, -1 ) ) );
						if( '},' == $match[3] ) return $offset;
					}
					else  $offset = $this->pg_array_parse( $text, $output, $limit, $offset+1 );
				}
				while( $limit > $offset );
		}
	*/


	public function getPGArray1D($input)
	{
		$pgArray = substr(substr(trim($input), 1), 0, -1);
		$v1 = explode(',', $pgArray);
		if ($v1 == ['']) {
			return [];
		}
		$inside = false;
		$out = [];
		$o = 0;
		foreach ($v1 as $word) {
			if ($word[0] == '"') {
				$inside = true;
				$word = substr($word, 1);
			}
			if (in_array($word[strlen($word) - 1], ['"'])
				&& !in_array($word[strlen($word) - 2], ['\\'])
			) {
				$inside = false;
				$word = substr($word, 0, -1);
			}
			$out[$o] .= stripslashes($word); // strange but required
			if (!$inside) {
				$o++;
			}
		}
		//debug($input, $pgArray, $out);
		return $out;
	}

	public function __toString2()
	{
		return $this->setPGArray($this->data);
	}

	public function getPGArrayFromJSON_bad($s)
	{
		$deepData = $this->pg_array_parse($s);

		$tokens = [];
		$tokens[] = strtok($s, '{,}"');
		do {
			$token1 = strtok('{,}"');
			$tokens[] = $token1;
		} while ($token1 != null);
//		debug($tokens);

		$keys = [];
		foreach ($tokens as $chr) {
			if (str_endsWith($chr, ':')) {
				$keys[] = substr($chr, 0, -1);
			}
		}
		debug($s, $deepData, $keys);

		$arrays = array_filter($deepData, function ($el) {
			return is_array($el);
		});
//		debug($arrays);

		debug(sizeof($keys), sizeof($arrays), $keys);
		$deepDataMerged = [];
		if (sizeof($keys) == sizeof($arrays)) {
			foreach ($deepData as $key => $val) {
				if (is_array($val)) {
					$key = current($keys);
					next($keys);
					$deepDataMerged[$key] = $val;
				} else {
					$deepDataMerged[$key] = $val;
				}
			}
		} else {
			$deepDataMerged = $deepData;
		}
		return $deepDataMerged;
	}

	/**
	 * https://stackoverflow.com/questions/3068683/convert-postgresql-array-to-php-array
	 * @param $s
	 * @param int $start
	 * @param null $end
	 * @return array|null
	 */
	public function pg_array_parse($s, $start = 0, &$end = null)
	{
		if (empty($s) || $s[0] != '{') {
			return null;
		}
		$return = [];
		$string = false;
		$quote = '';
		$len = strlen($s);
		$v = '';
		for ($i = $start + 1; $i < $len; $i++) {
			$ch = $s[$i];

			if (!$string && $ch == '}') {
				if ($v !== '' || !empty($return)) {
					$return[] = $v;
				}
				$end = $i;
				break;
			} elseif (!$string && $ch == '{') {
				$v = $this->pg_array_parse($s, $i, $i);
			} elseif (!$string && $ch == ',') {
				$return[] = $v;
				$v = '';
			} elseif (!$string && ($ch == '"' || $ch == "'")) {
				$string = true;
				$quote = $ch;
			} elseif ($string && $ch == $quote && $s[$i - 1] == "\\") {
				$v = substr($v, 0, -1) . $ch;
			} elseif ($string && $ch == $quote && $s[$i - 1] != "\\") {
				$string = false;
			} else {
				$v .= $ch;
			}
		}

		return $return;
	}

	public function getPGArrayFromJSON($s)
	{
		$result = [];
		$collect = false;
		$buffer = [];
		$deepData = $this->pg_array_parse($s);
		foreach ($deepData as $part) {
			if (str_contains($part, ':{')) {
				$collect = true;
				$buffer = [];
			}
			if ($collect) {
				$buffer[] = $part;
			} else {
				$result[] = $part;
			}

			if (str_endsWith($part, '}')) {
				$result[] = implode(',', $buffer);
				$buffer = [];
				$collect = false;
			}
		}
//		debug($deepData, $result);
		return $result;
	}

	public function unnest($sElements)
	{
		$rows = $this->db->fetchAll("SELECT unnest('" . pg_escape_string($sElements) . "'::text[])");
//		$rows = $this->db->fetchAll("SELECT unnest(string_to_array('".pg_escape_string($sElements)."'::text, ','))");
		return $rows;
	}

}
