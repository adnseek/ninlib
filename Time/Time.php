<?php

/**
 * This is the time inclusive the date.
 *
 */

class Time
{

	const HUMAN = 'H:i';
	/**
	 * @var int
	 */
	public $time;
	/**
	 * @var string
	 */
	public $debug;

	/**
	 * @var string
	 */
	public $human;

	protected $format = 'Y-m-d H:i:s Z (U)';

	/**
	 * Append GMT for Greenwich
	 * @param string $input
	 * @param null $relativeTo
	 * @throws Exception
	 */
	public function __construct($input = null, $relativeTo = null)
	{
		//TaylorProfiler::start(__METHOD__.' ('.MySQL::getCaller().')');
		if (!is_null($input)) { // 0 is 1970-01-01 00:00:00
			if (is_string($input)) {
				if (is_null($relativeTo)) {
					$this->time = strtotime($input);
				} else {
					$this->time = strtotime($input, $relativeTo);
				}
			} elseif ($input instanceof Time) {
				$this->time = $input->getTimestamp(); // clone
				//debug('clone '.$this->getHumanDateTime());
			} elseif (is_numeric($input)) {
				$this->time = $input;
			} elseif (class_exists('Config')) {
				Config::getInstance()->log(__CLASS__ . '#' . __LINE__, __('"%1" is unrecognized as a valid date.', $input));
			}
		} else {
			if ($relativeTo === null) {
				$this->time = time();
			} else {
				$this->time = $relativeTo;
			}
		}
		$this->updateDebug();
		//TaylorProfiler::stop(__METHOD__.' ('.MySQL::getCaller().')');
	}

	/**
	 * DON'T USE getTimestamp() to get amount of seconds as it depends on the TZ
	 *
	 * @return int
	 */
	public function getTimestamp()
	{
		return $this->time;
	}

	public function updateDebug()
	{
		//TaylorProfiler::start(__METHOD__);
		$this->debug = $this->getISO();
		$this->human = $this->getHumanDateTime();
		//TaylorProfiler::stop(__METHOD__);
	}

	/**
	 * YMDTHISZ
	 *
	 * @return string
	 */
	public function getISO()
	{
		return gmdate('Ymd\THis\Z', $this->time);
	}

	/**
	 * @return string
	 */
	public function getHumanDateTime()
	{
		return date('Y-m-d H:i', $this->time);
	}

	/**
	 * @param null $input
	 * @param null $relativeTo
	 * @return static
	 */
	public static function make($input = null, $relativeTo = null)
	{
		$self = get_called_class();
		return new $self($input, $relativeTo);
	}

	/**
	 * Combines date and time and creates a new Time object
	 * @param $date
	 * @param $time
	 * @return Time
	 */
	public static function combine($date, $time)
	{
		if ($date instanceof Time) {
			$date = $date->getISODate();
		}
		if ($time instanceof Time) {
			$time = $time->getTime();
		}
		$string = $date . ' ' . $time;
		//debug($string);
		return new self($string);
	}

	public function __toString()
	{
		return $this->format($this->format);
	}

	/**
	 * Calls the date function
	 * @param $rules
	 * @return string
	 */
	public function format($rules)
	{
		if ($this->time) {
			$content = date($rules, $this->time);
		} else {
			$content = '';
		}
		return $content;
	}

	public function toSQL()
	{
		return date('Y-m-d H:i:s', $this->time);
	}

	/**
	 * @return int
	 */
	public function getGMTTimestamp()
	{
		return strtotime($this->getISODate() . ' ' . $this->getTime() . ' GMT');
	}

	/**
	 * System readable 2009-12-21
	 *
	 * @return string
	 */
	public function getISODate()
	{
		return date('Y-m-d', $this->time);
	}

	/**
	 * 12:21:15
	 *
	 * @param string $format
	 * @return string
	 */
	public function getTime($format = 'H:i:s')
	{
		return date($format, $this->time);
	}

	/**
	 * Doesn't modify self
	 *
	 * @param string $formula
	 * @return Time
	 */
	public function math($formula)
	{
		return new self(strtotime($formula, $this->time));
	}

	public function earlier(Time $than)
	{
		return $this->time < $than->time;
	}

	public function earlierOrEqual(Time $than)
	{
		return $this->time <= $than->time;
	}

	public function laterOrEqual(Time $than)
	{
		/*		debug(array(
					$this->time,
					$than->time,
				));
		*/
		return $this->time >= $than->time;
	}

	/**
	 * @return string
	 */
	public function getMySQL()
	{
		return date('Y-m-d H:i:s', $this->time);
	}

	/**
	 * @return string
	 */
	public function getMySQLUTC()
	{
		return gmdate('Y-m-d H:i:s', $this->time);
	}

	/**
	 * @return string
	 */
	public function getDate()
	{
		return date('d.m.Y', $this->time);
	}

	/**
	 * <span class="time">in 10 hours</span>
	 *
	 * @return HtmlString
	 */
	public function render()
	{
		return new HtmlString('<time datetime="' . $this->getDateTime() . '"
			class="time" title="' . $this->getDateTime() . '">' . $this->in() . '</span>');
	}

	/**
	 * This is like ISO but human readable
	 * If you need human-human use getHumanDateTime()
	 * @return string
	 */
	public function getDateTime()
	{
		return date('Y-m-d H:i:s', $this->time);
	}

	/**
	 * (C) yasmary at gmail dot com
	 * Link: http://de.php.net/time
	 *
	 * @return string
	 */
	public function in()
	{
		TaylorProfiler::start(__METHOD__);
		$periods = [
			"second",
			"minute",
			"hour",
			"day",
			"week",
			"month",
			"year",
			"decade"
		];
		$pperiods = [
			"seconds",
			"minutes",
			"hours",
			"days",
			"weeks",
			"months",
			"years",
			"decades"
		];
		foreach ($periods as &$period) {
			$period = __($period);
		}
		foreach ($pperiods as &$period) {
			$period = __($period);
		}
		$lengths = ["60", "60", "24", "7", "4.35", "12", "10"];

		$now = time();
		$unix_date = $this->time;

		// check validity of date
		if (empty($unix_date)) {
			return __("Bad date");
		}

		// is it future date or past date
		if ($now > $unix_date) {
			$difference = $now - $unix_date;
			$tense = __("ago");

		} else {
			$difference = $unix_date - $now;
			$tense = __("from now");
		}

		for ($j = 0; $difference >= $lengths[$j] && $j < count($lengths) - 1; $j++) {
			$difference /= $lengths[$j];
		}

		$difference = round($difference);

		if (!$difference) {
			$content = __('Just now');
		} elseif ($difference != 1) {
			$period = $pperiods[$j];
			$content = "$difference $period {$tense}";
		} else {
			$period = $periods[$j];
			$content = "$difference $period {$tense}";
		}

		TaylorProfiler::stop(__METHOD__);
		return $content;
	}

	/**
	 * Displays start of an hour with larger font
	 *
	 * @return string
	 */
	public function renderCaps()
	{
		TaylorProfiler::start(__METHOD__);
		$noe = $this->format('H:i');
		if ($noe[3] . $noe[4] !== '00') {
			//$noe = '<small>'.$noe.'</small>';
			$noe = new HTMLTag('small', [], $noe);
		}
		TaylorProfiler::stop(__METHOD__);
		return $noe;
	}

	/**
	 * Use it for all the duration times
	 */
	public function gmFormat($rules)
	{
		return gmdate($rules, $this->time);
	}

	/**
	 * Almost like getISO() but without timezone: 'Y-m-d H:i:s'
	 *
	 * @return string
	 */
	public function getSystem()
	{
		return date('Y-m-d H:i:s', $this->time);
	}

	/**
	 * Modifies self!
	 *
	 * @param Time $plus
	 * @param bool $debug
	 * @return Time
	 */
	public function add(Time $plus, $debug = false)
	{
		//TaylorProfiler::start(__METHOD__);
		$this->time = $this->plus($plus, $debug)->time;
		$this->updateDebug();
		//TaylorProfiler::stop(__METHOD__);
		return $this;
	}

	/**
	 * Does not modify itself
	 * @param Time $plus
	 * @param bool $debug
	 * @return Time
	 */
	public function plus(Time $plus, $debug = false)
	{
		//TaylorProfiler::start(__METHOD__);
		/*$format = '+ '.
			($plus->format('Y')-1970).' years '.
			($plus->format('m')-1).' months '.
			($plus->format('d')-1).' days '.
			$plus->format('H').' hours '.
			$plus->format('i').' minutes '.
			$plus->format('s').' seconds';
		$new = strtotime($format, $this->time);*/
		//$format = gmmktime($plus->format('H'), $plus->format('i'), $plus->format('s'), $plus->format('m'), $plus->format('d'), $plus->format('Y'));
		$format = $plus->getTimestamp();
		$new = $this->time + $format;

		if (0) {
			echo $this . ' + ' . $format . ' (' . date('Y-m-d H:i:s', is_long($format) ? $format : 0) . ') = [' . $new . ']<br>';
		}
		$new = new self($new);
		//TaylorProfiler::stop(__METHOD__);
		return $new;
	}

	/**
	 * Modifies self!
	 *
	 * @param Duration $plus
	 * @param bool $debug
	 * @return static
	 */
	public function addDur(Duration $plus, $debug = false)
	{
		TaylorProfiler::start(__METHOD__);
		$this->time = $this->time + $plus->getTimestamp();
		$this->updateDebug();
		TaylorProfiler::stop(__METHOD__);
		return $this;
	}

	/**
	 * Modifies self!
	 *
	 * @param Time $plus
	 * @param bool $debug
	 * @return Time
	 */
	public function substract(Time $plus, $debug = false)
	{
		//TaylorProfiler::start(__METHOD__);
		$this->time = $this->minus2($plus, $debug)->time;
		$this->updateDebug();
		//TaylorProfiler::stop(__METHOD__);
		return $this;
	}

	/**
	 * Does not modify itself
	 * @param Time $plus
	 * @param bool $debug
	 * @return Time
	 */
	public function minus2(Time $plus, $debug = false)
	{
		//TaylorProfiler::start(__METHOD__);
		//$format = gmmktime($plus->format('H'), $plus->format('i'), $plus->format('s'), $plus->format('m'), $plus->format('d'), $plus->format('Y'));
		$format = $plus->getTimestamp();
		$new = $this->time - $format;
		if ($debug) echo $this . ' - ' . $format . ' = ' . $new . '<br>';
		$static = get_class($this);
		$new = new $static($new);
		//TaylorProfiler::stop(__METHOD__);
		return $new;
	}

	public function plusDur(Duration $plus)
	{
		return new self($this->time + $plus->getTimestamp());
	}

	public function getDiff(Time $t2)
	{
		return new Duration($this->time - $t2->time);
	}

	/**
	 * Modifies itself according to the format.
	 * Truncate by hour: Y-m-d H:00:00
	 * @param string $format
	 * @return $this
	 */
	public function modify($format)
	{
		/*$db = $GLOBALS['db'];
		/* @var $db dbLayerPG */
		/*$key = __METHOD__.' ('.$db->getCaller(2).', '.$db->getCaller(3).')';
		*/
		$key = __METHOD__;
		TaylorProfiler::start($key);
		$t = new Time($this->format($format));
		$this->time = $t->getTimestamp();
		$this->updateDebug();
		TaylorProfiler::stop($key);
		return $this;
	}

	/**
	 * Creates a new Time by formatting itself to a string first
	 * @param $format
	 * @return Time
	 */
	public function getModify($format)
	{
		//TaylorProfiler::start(__METHOD__);
		$new = new Time($this->format($format));
		//TaylorProfiler::stop(__METHOD__);
		return $new;
	}

	public function getAdjustedForTZ()
	{
		$isoWithoutZ = date('Y-m-d H:i:s', $this->getTimestamp()) . ' UTC';
		//debug($isoWithoutZ);
		return strtotime($isoWithoutZ);
	}

	public function getAdjustedForUTC()
	{
		$isoWithoutZ = gmdate('Y-m-d H:i:s', $this->getTimestamp());
		$newTS = strtotime($isoWithoutZ);
		//debug($this, $isoWithoutZ, $newTS, date('Y-m-d H:i:s', $newTS));
		return $newTS;
	}

	public function getDuration()
	{
		TaylorProfiler::start(__METHOD__);
		$periods = [
			"second" => 1,
			"minute" => 60,
			"hour" => 60,
			"day" => 24,
			"week" => 7,
			"month" => 30.5,
			"year" => 12,
			"decade" => 10,
		];
		$pmultiple = 1;
		foreach ($periods as $name => &$multiple) {
			$multiple *= $pmultiple;
			$pmultiple = $multiple;
		}
		unset($multiple); // just in case
		$periods = array_reverse($periods);

		$collect = [];
		//$timestamp = $this->getAdjustedForTZ();
		$timestamp = $this->time;
		//debug($this->time, $timestamp); exit();
		foreach ($periods as $name => $pe) {
			$result = $timestamp / $pe;
			//debug(array($timestamp, $result));
			if ($result >= 1) {
				$collect[$name] = floor($result);
				$timestamp = $timestamp % $pe;
			}
		}

		//debug($collect);
		if ($collect) {
			foreach ($collect as $name => &$val) {
				$val = $val . ' ' . $name . ($val > 1 ? 's' : '');
			}
			$content = implode(', ', $collect);
		} else {
			$content = 'no time';
		}
		//exit();
		TaylorProfiler::stop(__METHOD__);
		return $content;
	}

	/**
	 * Modify using strtotime
	 * @param $strtotime
	 * @return Time
	 */
	public function adjust($strtotime)
	{
		$newTime = strtotime($strtotime, $this->time);
		//debug($this->time, $strtotime, $newTime);
		$this->time = $newTime;
		$this->updateDebug();
		return $this;
	}

	public function getTimeIn($tz)
	{
		$temp = date_default_timezone_get();
		date_default_timezone_set($tz);
		$content = $this->format('H:i');
		date_default_timezone_set($temp);
		return $content;
	}

	public function getTwo()
	{
		return strtolower(substr($this->format('D'), 0, 2));
	}

	/**
	 * @return Duration
	 */
	public function getDurationObject()
	{
		return new Duration($this->time);
	}

	public function older($sDuration)
	{
		$duration = new Duration($sDuration);
		$difference = Time::makeInstance('now')->minus($this);
		$older = $difference->later($duration);
		return $older;
	}

	/**
	 * Does not modify itself
	 * @param Time $plus
	 * @param bool $debug
	 * @return Time
	 */
	public function minus(Time $plus, $debug = false)
	{
		return $this->minus2($plus, $debug);
		//TaylorProfiler::start(__METHOD__);
		$format = '- ' .
			($plus->format('Y') - 1970) . ' years ' .
			($plus->format('m') - 1) . ' months ' .
			($plus->format('d') - 1) . ' days ' .
			$plus->format('H') . ' hours ' .
			$plus->format('i') . ' minutes ' .
			$plus->format('s') . ' seconds ago';
		$new = strtotime($format, $this->time);
		$static = get_class($this);
		$new = new $static($new);
		if ($debug) echo $this . ' ' . $format . ' = ' . $new . '<br>';
		//TaylorProfiler::stop(__METHOD__);
		return $new;
	}

	/**
	 * Only to chain methods
	 *
	 * @static
	 * @param $str
	 * @param null $rel
	 * @return static
	 */
	public static function makeInstance($str, $rel = null)
	{
		$static = get_called_class();
		return new $static($str, $rel);
	}

	public function later(Time $than)
	{
		return $this->time > $than->time;
	}

	/**
	 * @return Date
	 */
	public function getDateObject()
	{
		return new Date($this->getTimestamp());
	}

	public function getHTMLDate()
	{
		return new HtmlString('<time datetime="' . $this->getISODateTime() . '">' . $this->getHumanDate() . '</time>');
	}

	public function getISODateTime()
	{
		return date('Y-m-d H:i:s', $this->time);
	}

	/**
	 * Human readable 21.02.1979
	 *
	 * @return string
	 */
	public function getHumanDate()
	{
		return date('d.m.Y', $this->time);
	}

	public function getHTMLTime()
	{
		return new HtmlString('<time datetime="' . $this->getISODateTime() . '">' . $this->getHumanTime() . '</time>');
	}

	/**
	 * 12:21
	 *
	 * @return string
	 */
	public function getHumanTime()
	{
		return date('H:i', $this->time);
	}

	public function getHTMLTimeGMT()
	{
		return new HtmlString('<time datetime="' . $this->getISODateTime() . '">' . $this->getHumanTimeGMT() . '</time>');
	}

	/**
	 * 12:21
	 *
	 * @return string
	 */
	public function getHumanTimeGMT()
	{
		//$zone = datefmt_get_timezone();
		$zone = date_default_timezone_get();
		//datefmt_set_timezone('GMT');
		date_default_timezone_set('GMT');
		$str = date('H:i', $this->time);
		//datefmt_set_timezone($zone);
		date_default_timezone_set($zone);
		return $str;
	}

	public function setFormat($string)
	{
		$this->format = $string;
	}

	public function makeGMT()
	{
		$this->setTimestamp(strtotime(gmdate('Y-m-d H:i:s', $this->time) . ' GMT', 0));
		return $this;
	}

	public function setTimestamp($time)
	{
		$this->time = $time;
		$this->updateDebug();
	}

	public function addDate(Date $date)
	{
		$this->setTimestamp(strtotime(date('H:i:s', $this->time), $date->getTimestamp()));
	}

	public function getSince()
	{
		return new Duration($this->getTimestamp() - time());
	}

	public function setHis($H, $i = 0, $s = 0)
	{
		$this->time = strtotime(date('Y-m-d') . ' ' . $H . ':' . $i . ':' . $s);
		$this->updateDebug();
	}

	public function getAge()
	{
		return new Duration(time() - $this->getTimestamp());
	}

	public function getHumanDateOrTime()
	{
		if ($this->isToday()) {
			return $this->getHumanTime();
		} else {
			return $this->getHumanDate();
		}
	}

	public function isToday()
	{
		return date('Y-m-d', $this->getTimestamp()) == date('Y-m-d');
	}

	public function getDay()
	{
		return $this->format('d');
	}

	public function isFuture()
	{
		return $this->time > time();
	}

	public function isPast()
	{
		return $this->time < time();
	}

	public function addTime($sTime)
	{
		$duration = new Duration($sTime);
		nodebug($duration->getRemHours(),
			$duration->getRemMinutes(),
			$duration->getRemSeconds());
		$base = $this->getTimestamp();
		$time = strtotime(' + ' . $duration->getRemHours() . ' hours', $base);
		$time = strtotime(' + ' . $duration->getRemMinutes() . ' minutes', $time);
		$time = strtotime(' + ' . $duration->getRemSeconds() . ' seconds', $time);
		$this->setTimestamp($time);
		return $this;
	}

	public function getYear()
	{
		return date('Y', $this->time);
	}

	public function getMonth()
	{
		return date('m', $this->time);
	}

	public function getWeek()
	{
		return date('W', $this->time);
	}

	public function olderThan($seconds)
	{
		return $this->getTimestamp() < (time() - $seconds);
	}

	public function youngerThan($seconds)
	{
		return $this->getTimestamp() > (time() - $seconds);
	}

	public function isMultipleOf($seconds)
	{
		return ($this->getTimestamp() % $seconds) == 0;
	}

	public function toDateTime()
	{
		return new DateTime($this->getTimestamp());
	}

}
