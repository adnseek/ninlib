<?php

use spidgorny\nadlib\HTTP\URL;

/**
 * Singleton
 * @phpstan-consistent-constructor
 */
class LocalLangDB extends LocalLang
{

	public $table = 'interface';

	/**
	 * @var MySQL|DBLayerBase
	 */
	protected $db;

	/**
	 * Source data from DB. Used in Localize.
	 * @var array
	 */
	protected $rows = [];

	/**
	 * @param $forceLang
	 */
	public function __construct($forceLang = null)
	{
		parent::__construct($forceLang);
	}

	/**
	 * Why is it not called from the constructor?
	 * Because we need to specify the desired language $this->lang
	 */
	public function init()
	{
		$config = Config::getInstance();
		$this->db = $config->getDB();
		$this->table = $config->prefixTable($this->table);
		$this->rows = $this->readDB($this->lang);
		if ($this->rows) {
			$apRows = ArrayPlus::create($this->rows);
			$this->codeID = $apRows->column_assoc('code', 'id')->getData();
			$apRows = ArrayPlus::create($this->rows);
			$this->ll = $apRows->column_assoc('code', 'text')->getData();
		}
	}

	public static function getInstance($forceLang = null, $filename = null)
	{
		static $instance = null;
		if (!$instance) {
			$instance = new static($forceLang);
			$instance->init();
		}
		return $instance;
	}

	public function readDB($lang)
	{
		//debug_pre_print_backtrace();
		$res = $this->db->getTableColumnsEx($this->table);
		if ($res) {
			// wrong query
			/*$rows = $this->db->fetchSelectQuery($this->table.
				" AS a RIGHT OUTER JOIN ".$this->table." AS en
				ON ((a.code = en.code OR a.code IS NULL) AND en.lang = 'en')", array(
				'a.lang' => new SQLAnd(array(
					'a.lang' => new SQLWhereEqual('a.lang', $lang),
					'a.lang ' => new SQLWhereEqual('a.lang', NULL),
					)
				),
				'a.lang' => $lang,
				'en.lang' => 'en',
			), 'ORDER BY id',
				'coalesce(a.id, en.id) AS id,
				coalesce(a.code, en.code) AS code,
				coalesce(a.lang, en.lang) AS lang,
				coalesce(a.text, en.text) AS text,
				a.page');
			debug($this->db->lastQuery, sizeof($rows), first($rows));*/
			$rows = $this->db->fetchSelectQuery($this->table, [
				'lang' => $lang,
			], 'ORDER BY id');
			$rows = ArrayPlus::create($rows)->IDalize('id')->getData();
		} else {
			debug($this->db->lastQuery);
			throw new Exception('No translation found in DB');
		}
		return $rows;
	}

	/**
	 * Instead of searching if the original language (en) record exists
	 * it tries to insert and then catches the UNIQUE constraint exception.
	 * @param $code
	 * @throws Exception
	 */
	public function saveMissingMessage($code)
	{
		nodebug([
			'object' => spl_object_hash($this),
			'method' => __METHOD__,
			'DEVELOPMENT' => DEVELOPMENT,
			'code' => $code,
			'$this->saveMissingMessages' => $this->saveMissingMessages,
			'$this->db' => !!$this->db,
			'$this->ll[code]' => ifsetor($this->ll[$code]),
		]);
		if (DEVELOPMENT && $code && $this->saveMissingMessages && $this->db) {
			try {
				$where = [
					'code' => $code,
					'lang' => $this->defaultLang,        // is maybe wrong to save to the defaultLang?
				];
				$insert = [
					'text' => $code,                    // not empty, because that's how it will be translated
					'page' => Request::getInstance()->getURL(),
				];
				$cols = $this->db->getTableColumns($this->table);
				$user = Config::getInstance()->getUser();
				if (ifsetor($cols['cuser'])) {
					$insert['cuser'] = $user->id;
				}
				if (ifsetor($cols['muser'])) {
					$insert['muser'] = $user->id;
				}
				$res = $this->db->runInsertNew($this->table, $where, $insert);
				//debug($code, $this->db->lastQuery, $this->db->numRows($this->db->lastResult), $this->db->affectedRows());
				$this->ll[$code] = $code;
				$this->codeID[$code] = $this->db->lastInsertID($res);
				//debug($this->db->lastQuery);
			} catch (Exception $e) {
				Index::getInstance()->log(__METHOD__, ['error' => $e->getMessage()]);
			}
		}
	}

	/**
	 * Dangerous - may overwrite
	 * @param array $data
	 * @throws AccessDeniedException
	 * @throws Exception
	 */
	public function updateMessage(array $data)
	{
		$user = Config::getInstance()->getUser();
		if ($user->isAdmin()) {
			$llm = new LocalLangModel();
			$llm->findInDB(['lang' => $data['lang'], 'code' => $data['code']]);
			if ($llm->id) {
				$llm->update([
					'text' => $data['text'],
				]);
			} else {
				$llm->insert($data);
			}
		} else {
			throw new AccessDeniedException();
		}
	}

	public function getRow($id)
	{
		return ifsetor($this->rows[$id]);
	}

	public function showLangSelection()
	{
		$content = '';
		$stats = $this->getLangStats();
		if (sizeof($stats) > 1) {           // don't show selection of just one language
			foreach ($stats as $row) {
				$u = URL::getCurrent();
				$u->setParam('setLangCookie', $row['lang']);
				$title = $row['lang'] . ' (' . $row['percent'] . ')';
				$content .= '<a href="' . $u->buildURL() . '" title="' . $title . '">
					<img src="img/' . $row['lang'] . '.gif" width="20" height="12" />
				</a>';
			}
		}
		//debug($_SERVER['REQUEST_URI'], $u, $u->buildURL());
		return $content;
	}

	public function getLangStats()
	{
		$en = $this->readDB('en');
		$countEN = sizeof($en) ? sizeof($en) : 1;
		$langs = array_combine($this->possibleLangs, $this->possibleLangs);
		foreach ($langs as &$lang) {
			$rows = $this->readDB($lang);
			$lang = [
				'img' => new HtmlString('<img src="img/' . $lang . '.gif" width="20" height="12" />'),
				'lang' => $lang,
				'rows' => sizeof($rows),
				'percent' => number_format(sizeof($rows) / $countEN * 100, 0) . '%',
			];
		}
		return $langs;
	}

}
