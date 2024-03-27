<?php

class TimeChart
{

	/**
	 * @var DBInterface
	 */
	public $db;

	public $table;

	/**
	 * @var array
	 */
	public $where;

	/**
	 * @var
	 */
	public $groupBy;

	public $options = [
		'year-month' => '%Y-%m',
		'year' => '%Y',
		'year-month-day' => '%Y-%m-%d',
		'year-week' => '%Y-W%W',
		'dow' => '%w',
	];

	public $barWidths = [
		'year-month' => 24,
		'year' => 360,
		'year-month-day' => 0.9,
		'year-week' => 6,
		'dow' => 0.9,
	];

	/**
	 * @var array[array]
	 */
	public $data;

	public $title = 'Appointments';

	public $query;

	public $dow = [
		'1' => 'Monday',
		'2' => 'Tuesday',
		'3' => 'Wednesday',
		'4' => 'Thursday',
		'5' => 'Friday',
		'6' => 'Saturday',
		'0' => 'Sunday',
	];

	/**
	 * @var string
	 */
	public $timeField;

	public function __construct($table, array $where, $timeField, $groupBy = 'year-month')
	{
		$this->db = Config::getInstance()->db;
		$this->table = $table;
		$this->where = $where;
		$this->timeField = $timeField;
		$this->groupBy = $groupBy;
		if ($this->db->getScheme() == 'mysql') {
			$this->options['year-week'] = '%Y-W%u';
		}
	}

	public function fetch()
	{
		if (!$this->data) {
			$sqlDate = $this->getSQLForTime();
			$where = $this->where;
			//$where["'1970-01'"] = new AsIsOp("!= ".$sqlDate);
			$this->query = $this->db->getSelectQuery($this->table, $where,
				'GROUP BY ' . $sqlDate . '
				 ORDER BY ' . $sqlDate,
				'"' . $this->title . '" as line,
				' . $sqlDate . ' as "' . $this->groupBy . '", count(*) AS amount');
			$this->data = $this->db->fetchAll($this->query);
		}
	}

	public function getSQLForTime()
	{
		$content = '';
		$dateFormat = $this->options[$this->groupBy];
		if (!$dateFormat) {
			throw new Exception(__METHOD__);
		}
		$timeField = $this->db->quoteKey($this->timeField);
		if ($this->db->getScheme() == 'mysql') {
			$content = 'date_format(' . $timeField . ', "' . $dateFormat . '")';
		} elseif ($this->db->getScheme() == 'sqlite') {
			$content = 'strftime("' . $dateFormat . '", ' . $timeField . ')';
		}
		return $content;
	}

	public function render()
	{
		$this->fetch();
		if ($this->data) {
			$f = $this->getFlot();
			$content = $f->render($this->groupBy);
			//$s = new slTable($data);
			//$content .= $s;
		} else {
			//debug($this->query);
			$content = '';
		}
		return $content;
	}

	public function getFlot()
	{
		$this->fetch();
		$f = new Flot($this->data, 'line', $this->groupBy, 'amount');
		$f->flotPath = 'vendor/flot/flot/';
		$f->width = '100%';
		$f->height = '100px';
		$f->barWidth = 1000 * 60 * 60 * 24 * $this->barWidths[$this->groupBy];
		$f->min *= 1.1;
		$f->max *= 1.1;
		$f->setMinMax();
		return $f;
	}

	public function __toString()
	{
		return $this->render() . '';
	}

}
