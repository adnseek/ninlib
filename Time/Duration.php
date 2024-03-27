<?php
/**
 * A class for making time periods readable.
 *
 * This class allows for the conversion of an integer
 * number of seconds into a readable string.
 * For example, '121' into '2 minutes, 1 second'.
 *
 * If an array is passed to the class, the associative
 * keys are used for the names of the time segments.
 * For example, array('seconds' => 12, 'minutes' => 1)
 * into '1 minute, 12 seconds'.
 *
 * This class is plural aware. Time segments with values
 * other than 1 will have an 's' appended.
 * For example, '1 second' not '1 seconds'.
 *
 * @author      Aidan Lister <aidan@php.net>
 * @version     1.2.1
 * @link        http://aidanlister.com/repos/v/Duration.php
 */

class Duration extends Time
{

	public $periods = [
		'year' => 31556926,
		'month' => 2629743,
		'week' => 604800,
		'day' => 86400,
		'hour' => 3600,
		'minute' => 60,
		'second' => 1
	];

	public function __construct($input = null)
	{
		if ($input instanceof Time) {
			$this->time = $input->time;
		} elseif (is_string($input)) {
			$temp = self::fromHuman($input);
			$this->time = $temp->getTimestamp();
			if (!$this->time) { // parsing failed
				parent::__construct($input . ' ', 0);    // GMT removed as it gives from '3 days' a value of '3d 1h'
			}
		} else {
			$this->time = $input;
		}
		$this->updateDebug();
	}

	public static function fromSeconds($ini_get)
	{
		return new Duration($ini_get);
	}

	public function format($rules)
	{
		die(__METHOD__ . ' - don\'t use.');
	}

	public function getTime($format = 'H:i:s')
	{
		return gmdate($format, $this->time);
	}

	public function nice($perCount = 2)
	{
		return $this->toString($perCount);
	}

	public function short()
	{
		$h = floor($this->time / 3600);
		$m = floor($this->time % 3600 / 60);
		$content = [];
		if ($h) {
			$content[] = $h . 'h';
		}
		if ($m) {
			$content[] = $m . 'm';
		}
		$content = implode('&nbsp;', $content);
		$content = $content ? $content : '-';
		return $content;
	}

	/**
	 * Parses the human string like '24h 10m'
	 * No spaces allowed between the number and value
	 * @param string $string
	 * @return Duration
	 */
	public static function fromHuman($string)
	{
		$total = 0;
		$parts = trimExplode(' ', $string);
		foreach ($parts as $p) {
			$value = floatval($p);
			$uom = str_replace($value, '', $p);
			//debug($p, $value, $uom);
			switch ($uom) {
				case 's':
				case 'sec':
				case 'second':
				case 'seconds':
					$total += $value * 1;
					break;
				case 'm':
				case 'min':
				case 'minute':
				case 'minutes':
					$total += $value * 60;
					break;
				case 'h':
				case 'hr':
				case 'hrs':
				case 'hour':
				case 'hours':
					$total += $value * 60 * 60;
					break;
				case 'd':
				case 'day':
				case 'days':
					$total += $value * 60 * 60 * 24;
					break;
				case 'w':
				case 'wk':
				case 'week':
				case 'weeks':
					$total += $value * 60 * 60 * 24 * 7;
					break;
				case 'mon':
				case 'month':
				case 'months':
					$total += $value * 60 * 60 * 24 * 30;
					break;
				case 'y':
				case 'yr':
				case 'yrs':
				case 'year':
				case 'years':
					$total += $value * 60 * 60 * 24 * 365;
					break;
			}
		}
		if (!$total) {
			$totalBefore = $total;
			$tz = date_default_timezone_get();
			date_default_timezone_set('UTC');
			$total = strtotime($string . ' UTC', 0);
			//debug($string, $totalBefore, $tz, date_default_timezone_get(), $total, $total/60/60);
			date_default_timezone_set($tz);
		}
		return new Duration($total);
	}

	/**
	 * Return human-readable time units
	 * @return string
	 */
	public function __toString()
	{
		//return floor($this->time / 3600/24).gmdate('\d H:i:s', $this->time).' ('.$this->time.')';
		return $this->toString();
	}

	/**
	 * All in one method
	 *
	 * @param int $perCount
	 * @return  string
	 */
	public function toString($perCount = 2)
	{
		$content = '';
		$duration = $this->int2array();
		//debug($duration);

		if (is_array($duration)) {
			$duration = array_slice($duration, 0, $perCount, true);
			$content .= $this->array2string($duration);
			//debug($this->time, $periods, $duration, $content);
			if ($this->time < 0) {
				$content .= ' ' . __('ago');
			}
		} else {
			$content .= __('just now');
		}

		return $content;
	}


	/**
	 * Return an array of date segments.
	 * Must be public for Trip
	 *
	 * @return       mixed An array containing named segments
	 * @internal param int $seconds Number of seconds to be parsed
	 */
	public function int2array()
	{
		$values = null;

		// Loop
		$seconds = (float)abs($this->time);
		foreach ($this->periods as $period => $value) {
			$count = floor($seconds / $value);

			if ($count == 0) {
				continue;
			}

			if ($count > 1) {
				$period .= 's';
			}
			$values[$period] = $count;
			$seconds = $seconds % $value;
		}

		return $values;
	}


	/**
	 * Return a string of time periods.
	 *
	 * @param mixed $duration An array of named segments
	 * @return       string
	 * @package      Duration
	 */
	protected static function array2string($duration)
	{
		if (!is_array($duration)) {
			return false;
		}

		$array = [];
		foreach ($duration as $key => $value) {
			$segment = abs($value) . ' ' . $key;
			// otherwise -1 years, -1 months ago

			$array[] = $segment;
		}

		$str = implode(', ', $array);
		return $str;
	}

	public function getTimestamp()
	{
		return $this->time;
	}

	public function less($sDuration)
	{
		if (is_string($sDuration)) {
			return $this->time < strtotime($sDuration, 0);
		} elseif ($sDuration instanceof Time) {
			return $this->earlier($sDuration);
		} else {
			throw new Exception(__METHOD__ . '#' . __LINE__);
		}
	}

	public function more($sDuration)
	{
		if (is_string($sDuration)) {
			return $this->time > strtotime($sDuration, 0);
		} elseif ($sDuration instanceof Time) {
			return $this->later($sDuration);
		} else {
			throw new Exception(__METHOD__ . '#' . __LINE__);
		}
	}

	public function getMinutes()
	{
		return $this->time / 60;
	}

	public function getHours()
	{
		return $this->time / 60 / 60;
	}

	public function getDays()
	{
		return $this->time / 60 / 60 / 24;
	}

	public function getRemHours()
	{
		return floor($this->time / 60 / 60);
	}

	public function getRemMinutes()
	{
		return floor($this->time % (60 * 60) / 60);
	}

	public function getRemSeconds()
	{
		return $this->time % (60);
	}

	public function biggerThan(Duration $d2)
	{
		return abs($this->time) > abs($d2->getTimestamp());
	}

	public function smallerThan(Duration $d2)
	{
		return abs($this->time) < abs($d2->getTimestamp());
	}

	public function getSeconds()
	{
		return $this->getTimestamp();
	}

}
