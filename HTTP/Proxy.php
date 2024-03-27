<?php

namespace nadlib;

use AsIs;
use AsIsOp;
use Config;
use DBInterface;
use Index;
use OODBase;

class Proxy extends OODBase
{

	public static $best = [];
	protected static $maxFail = 15;
	protected static $maxFailBest = 200;
	public $table = 'proxy';
	/**
	 * @var DBInterface
	 */
	public $db;
	public $ratio = 0;
	protected $titleColumn = 'proxy';

	public function __construct($row = null)
	{
		parent::__construct($row);
		$this->db = \Config::getInstance()->getDB();
		if ($this->data) {
			$this->ratio = $this->data['ok'] / max(1, $this->data['fail']);
		}
	}

	public static function getRandomOrBest($percentRandom = 50)
	{
		$proxy = null;
		$db = Config::getInstance()->getDB();
		/** @var AppController $c */
		$c = Index::getInstance()->controller;
		if (random_int(0, 100) < $percentRandom) { // 25%
			$row = $db->fetchSelectQuery('proxy', ['fail' => new AsIs('< ') . self::$maxFail],
				'ORDER BY rand() LIMIT 1');
			if ($row[0]) {
				$proxy = new Proxy($row[0]);
				$c->log(__METHOD__, 'Random proxy: ' . $proxy . ' (ratio: ' . $proxy->ratio . ')');
			} else {
				$c->log(__METHOD__, 'No proxy');
			}
		} else {
			$best = self::getBest();
			$idx = random_int(0, sizeof($best) - 1);
			$proxy = new Proxy($best[$idx]);
			$c->log('Best proxy (' . $idx . '): ' . $proxy . ' (ratio: ' . $proxy->ratio . ')', __METHOD__);
		}
		return $proxy;
	}

	public static function getBest($limit = 100)
	{
		if (!self::$best) {
			$db = Config::getInstance()->getDB();
			$rows = $db->fetchSelectQuery('proxy', [
				'fail' => new AsIsOp('< ' . self::$maxFailBest),
				//'ok' => new AsIs('> 0'),
			], '
			/*ORDER BY ok DESC, fail ASC*/
			ORDER BY ratio DESC
			LIMIT ' . $limit, '*, ok/fail AS ratio');
			//debug($rows);
			self::$best = $rows;
		}
		return self::$best;
	}

	/**
	 * @return array(342571/359601)
	 */
	public static function getProxies()
	{
		$db = Config::getInstance()->getDB();
		$row = $db->fetchSelectQuery('proxy', [], '', 'count(*)');    // total
		$p = new Proxy();
		$okProxy = $p->getOKcount();
		return [$okProxy, $row[0]['count(*)']];
	}

	public function getOKcount()
	{
		$rowOK = $this->db->fetchSelectQuery('proxy', [
			'fail' => new AsIsOp('< ' . self::$maxFail)
		], '', 'count(*)');
		return $rowOK[0]['count(*)'];
	}

	public function setProxy($proxy)
	{
		$this->data['proxy'] = $proxy;
	}

	public function __toString()
	{
		return $this->data['proxy'] . '';
	}

	public function getList()
	{
		$rows = $this->db->fetchSelectQuery('proxy', [], 'ORDER BY ok DESC, fail ASC LIMIT 100');
		return $rows;
	}

	public function fail()
	{
		if ($this->id) {
			$this->update(['fail' => $this->data['fail'] + 1]);
		}
	}

	public function ok()
	{
		if ($this->id) {
			$this->update(['ok' => $this->data['ok'] + 1]);
		}
	}

}
