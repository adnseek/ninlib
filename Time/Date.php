<?php

class Date extends Time
{
	const HUMAN = 'd.m.Y';
	const SYSTEM = 'Y-m-d';

	public $format = 'd.m.Y';

	public function __construct($input = null, $relativeTo = null)
	{
		parent::__construct($input, $relativeTo);
		//$this->modify('Y-m-d \G\M\T'); // very slow!
		$this->time = mktime(0, 0, 0, date('m', $this->time), date('d', $this->time), date('Y', $this->time));
		$this->updateDebug();
		if (is_null($relativeTo)) {
			//debug_pre_print_backtrace();
			//assert($this->time >= 0);
		}
	}

	public function updateDebug()
	{
		$this->debug = gmdate('Y-m-d H:i \G\M\T', $this->time);
		$this->human = $this->getHumanDateTime();
	}

	/**
	 * Copy/paste because to 'static'
	 * @param int $input
	 * @param int $relativeTo
	 * @return static
	 */
	public static function make($input = null, $relativeTo = null)
	{
		return new self($input, $relativeTo);
	}

	public static function fromEurope($format)
	{
		$parts = explode('.', $format);
		$parts = array_reverse($parts);
		$parts = implode('-', $parts);
		return new self($parts);
	}

	public static function fromHuman($str)
	{
		return new Date(strtotime($str));
	}

	/**
	 * @return string
	 * @deprecated This is using gmdate()
	 */
	public function getMySQL()
	{
		return date('Y-m-d', $this->time);
	}

	/**
	 * This is using gmdate()
	 * @return string
	 */
	public function getMySQLUTC()
	{
		return gmdate('Y-m-d', $this->time);
	}

	public function getGMT()
	{
		return gmdate('Y-m-d', $this->time);
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

	/**
	 * @param string $format d.m.Y
	 * @return HtmlString
	 */
	public function html($format = 'd.m.Y')
	{
		return new HtmlString('<time datetime="' . $this->getISO() . '">' . $this->format($format) . '</time>');
	}

	public function getISO()
	{
		return date('Y-m-d', $this->time);
	}

	public function days()
	{
		return $this->getTimestamp() / 60 / 60 / 24;
	}

	public function getSystem()
	{
		return $this->format('Y-m-d');
	}

	public function plusDur(Duration $plus)
	{
		return new self($this->time + $plus->getTimestamp());
	}

	public function minusDur(Duration $day1)
	{
		return new self($this->time - $day1->getTimestamp());
	}

	public function isWeekend()
	{
		return in_array($this->format('D'), ['Sat', 'Sun']);
	}

	public function getHumanMerged()
	{
		return date('Ymd', $this->time);
	}

	public function fromMerged($date)
	{
		$y = substr($date, 0, 4);
		$m = substr($date, 4, 2);
		$d = substr($date, 6, 2);
		$this->time = strtotime($y . '-' . $m . '-' . $d);
		$this->updateDebug();
	}

	/**
	 * Mon, Tue, Wed, Thu, Fri, Sat, Sun
	 * @return string
	 */
	public function getDOW()
	{
		return date('D', $this->time);
	}

	public function getApproximate()
	{
		return $this->getHTMLDate();
	}

	public function addTime($sTime)
	{
		$time = new Time($this->getTimestamp());
		$time->addTime($sTime);
		return $time;
	}

	public function isFuture()
	{
		return $this->getISODate() > date('Y-m-d');
	}

	public function isPast()
	{
		return $this->getISODate() < date('Y-m-d');
	}

}
