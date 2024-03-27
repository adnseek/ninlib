<?php

/**
 * Will read SQL file, parse it and compare with current DB proposing
 * ALTER TABLE statements
 */

use spidgorny\nadlib\HTTP\URL;

//$typo3 = AutoLoad::getInstance()->nadlibRoot.'vendor/typo3/';
//require_once($typo3.'class.t3lib_div.php');
////require_once('../vendor/typo3/class.t3lib_sqlparser.php');
//require_once($typo3.'sysext/core/Classes/Database/SqlParser.php');
////require_once('../vendor/typo3/class.t3lib_install_sql.php');
//require_once($typo3.'sysext/install/Classes/Sql/SchemaMigrator.php');
////require_once('../vendor/typo3/class.t3lib_db.php');
//require_once($typo3.'sysext/core/Classes/Database/DatabaseConnection.php');
////require_once('../vendor/typo3/class.t3lib_utility_math.php');
//require_once($typo3.'sysext/core/Classes/Utility/MathUtility.php');

class AlterDB extends AppControllerBE
{

	/**
	 * @var t3lib_install_Sql
	 */
	protected $installerSQL;

	protected $update_statements = [];

	protected $file;

	public function __construct()
	{
		parent::__construct();
		if (!$this->user || !$this->user->can('Admin')) {
			//throw new AccessDeniedException('Access Denied to '.__CLASS__);
			// access controlled by AlterDB::$public which is false
		}
		$this->file = $this->request->getTrim('file');
		$this->linker->linkVars['file'] = $this->file;
	}

	public function wrongApproach()
	{
		$query = "CREATE TABLE app_appointment (
  id int(11) NOT NULL auto_increment,
  ctime timestamp NOT NULL default CURRENT_TIMESTAMP,
  mtime timestamp NOT NULL default '2009-06-14 00:00:00',
  id_service integer(11) NOT NULL,
  from datetime NOT NULL,
  till datetime NOT NULL,
  canceled tinyint(1) NOT NULL default '0',
  id_client integer(11) default NULL,
  id_user integer(11) default NULL,
  comment text NOT NULL,
  PRIMARY KEY  (id),
  KEY id_client (id_client),
  KEY id_service (id_service),
  KEY id_user (id_user)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8 AUTO_INCREMENT=31 ;
";
		/*$query = "CREATE TABLE tx_ehoi_ship (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l10n_parent int(11) DEFAULT '0' NOT NULL,
	l10n_diffsource mediumtext,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	name tinytext,
	image text,
	desctiption text,

	PRIMARY KEY (uid),
	KEY parent (pid)
);";*/
		$query = $this->getQueryFrom('some file.sql');
//		$SQLparser = new t3lib_sqlparser();
//		$parsedQuery = $SQLparser->parseSQL($query);
		//debug($parsedQuery);
		//debug(substr($query, 0, 1000));
	}

	public function getQueryFrom($file)
	{
		$query = file_get_contents($file);
		$query = str_replace('`', '', $query);
		$query = preg_replace('/^--.*$/m', '', $query);
		$query = preg_replace('/^SET.*$/m', '', $query);
		$query = preg_replace('/^DROP.*$/m', '', $query);
		$query = preg_replace('/CONSTRAINT.*$/m', '', $query);
		return $query;
	}

	public function render()
	{
		$content = '';
		$content .= $this->getFileChoice();
		$content .= '<h1>' . $this->file . '</h1>';

		if ($this->file) {
			$this->initInstallerSQL();

			$cache = new MemcacheArray(__CLASS__);
			if (!$cache->exists($this->file) || $this->request->getBool('reload')) {
				//if (true) {
				$query = $this->getQueryFrom($this->file);
				$diff = $this->getDiff($query);
				$cache->set($this->file, $diff);
			} else {
				$content .= $this->makeLink('Reload', [
					'c' => __CLASS__,
					'file' => $this->file,
					'reload' => true,
				]);
				$diff = $cache->get($this->file);
			}

			$this->update_statements = $this->installerSQL->getUpdateSuggestions($diff);
			//debug($diff, $this->update_statements);

			$this->performAction($this->detectAction());    // only after $this->update_statements are set

			$content .= $this->showDifferences($diff);
			//$content .= getDebug($diff);

			//$this->installerSql->performUpdateQueries($update_statements['add'],
		}
		return $content;
	}

	public function getFileChoice()
	{
		$menu = [];
		$sqlFolder = Config::getInstance()->appRoot . '/sql/';
		if (!is_dir($sqlFolder)) {
			return '<div class="error">No ' . $sqlFolder . '</div>';
		}
		/** @var $file SplFileInfo */
		foreach (new RecursiveDirectoryIterator($sqlFolder) as $file) {
			//debug($file);
			if ($file->getFilename() != '.' && $file->getFilename() != '..') {
				$menu[$file->getPathname()] = $file->getFilename();
			}
		}
		foreach ($menu as $key => &$name) {
			$name = new HTMLTag('a', [
				'href' => new URL('', [
					'c' => __CLASS__,
					'file' => $key,
				])
			], $name);
		}
		$content = '<ul><li>' . implode('</li><li>', $menu) . '</li></ul>';
		return $content;
	}

	public function initInstallerSQL()
	{
		TaylorProfiler::start(__METHOD__);
		$config = Config::getInstance();

		//$GLOBALS['TYPO3_DB'] = $t3db = new t3lib_DB();
//		$GLOBALS['TYPO3_DB'] = $t3db = new TYPO3\CMS\Core\Database\DatabaseConnection();
//		$t3db->connectDB($config->db_server, $config->db_user, $config->getDBpassword(), $config->db_database);
		//debug($t3db);
//		define('TYPO3_db', $config->db_database);

		//$this->installerSQL = new t3lib_install_Sql();
//		$this->installerSQL = new TYPO3\CMS\Install\Sql\SchemaMigrator();
		TaylorProfiler::stop(__METHOD__);
	}

	public function getDiff($query)
	{
		TaylorProfiler::start(__METHOD__);
		$FDfile = $this->installerSQL->getFieldDefinitions_fileContent($query);
		$FDfile = $this->filterDifferencesFile($FDfile);
		//$content .= getDebug($FDfile);

		$FDdb = $this->installerSQL->getFieldDefinitions_database();
		$FDdb = $this->filterDifferencesDB($FDdb);

		$diff = $this->installerSQL->getDatabaseExtra($FDfile, $FDdb);
		TaylorProfiler::stop(__METHOD__);
		return $diff;
	}

	public function filterDifferencesFile(array $FDfile)
	{
		foreach ($FDfile as $table => &$desc) {
			foreach ($desc['fields'] as $field => &$type) {
				$type = str_replace('AUTO_INCREMENT', 'auto_increment', $type);
				$type = str_replace('default NULL', '', $type);
				$type = str_replace('NOT NULL', '', $type);
				$type = str_replace('  ', ' ', $type);
				$type = trim($type);
			}
		}
		return $FDfile;
	}

	public function filterDifferencesDB(array $FDdb)
	{
		foreach ($FDdb as $table => &$desc) {
			$info = $this->db->getTableColumns($table);
			foreach ($desc['fields'] as $field => &$type) {

				// it doesn't include info about NULL/NOT NULL
				$infoField = $info[$field];
				//$type .= $infoField['Null'] == 'NO' ? ' NOT NULL' : ' NULL';

				//$type = str_replace('default NULL', '', $type);
				$type = str_replace("'CURRENT_TIMESTAMP'", 'CURRENT_TIMESTAMP', $type);
				$type = str_replace("on update", 'ON UPDATE', $type);
				$type = str_replace('  ', ' ', $type);
				$type = trim($type);
			}
		}
		return $FDdb;
	}

	public function showDifferences(array $diff)
	{
		$content = '';
		$content .= $this->showCreate();
		$content .= $this->showChanges($diff);
		$content .= $this->showExtras($diff);
		return $content;
	}

	public function showCreate()
	{
		$content = '';
		$update_statements = $this->update_statements;
		if ($update_statements['create_table']) {
			foreach ($update_statements['create_table'] as $md5 => $query) {
				$content .= '<pre>' . ($query);
				$content .= ' ' . $this->makeRelLink('CREATE', [
						'action' => 'do',
						'file' => $this->file,
						'key' => 'create_table',
						'query' => $md5,
					]);
				$content .= '</pre>';
			}
		}
		return $content;
	}

	public function showChanges(array $diff)
	{
		$content = '';
		$update_statements = $this->update_statements;
		//debug($diff['extra'], $update_statements['add']);
		if ($diff['diff']) foreach ($diff['diff'] as $table => $desc) {
			$list = [];
			foreach ($desc['fields'] as $field => $type) {
				$current = $diff['diff_currentValues'][$table]['fields'][$field];
				if ($type != $current) {
					//debug($type, $current); exit();
					$list[] = [
						'field' => $field,
						'file' => $type,
						'current' => $current,
						'sql' => $sql = $this->findStringWith($update_statements['change'], [$table, $field]),
						'do' => $this->makeRelLink('CHANGE', [
							'action' => 'do',
							'file' => $this->file,
							'key' => 'change',
							'query' => md5($sql),
						]),
					];
				}
			}
			$content .= $this->showTable($list, $table);
		}
		return $content;
	}


	public function showExtras(array $diff)
	{
		$content = '';
		$update_statements = $this->update_statements;
		if ($diff['extra']) {
			foreach ($diff['extra'] as $table => $desc) {
				$list = [];
				if (is_array($desc['fields'])) {
					foreach ($desc['fields'] as $field => $type) {
						$list[] = [
							'field' => $field,
							'file' => $type,
							'sql' => $sql = $this->findStringWith($update_statements['add'], [$table, $field]),
							'do' => $this->makeRelLink('ADD', [
								'action' => 'do',
								'file' => $this->file,
								'key' => 'add',
								'query' => md5($sql),
							]),
						];
					}
				}
				$content .= $this->showTable($list, $table);
			}
		}
		//debug($update_statements, Debug::LEVELS, 1);
		//debug($update_statements['create_table']);
		return $content;
	}

	public function showTable(array $list, $table)
	{
		$content = [];
		if ($list) {
			$s = new slTable($list, 'class="table"', [
				'field' => 'field',
				'file' => 'file',
				'current' => 'current',
				'sql' => 'sql',
				'do' => [
					'name' => 'do',
					'no_hsc' => true,
				],
			]);
			$content = $this->encloseInAA($s, $table, 'h2');
		}
		return $content;
	}

	public function findStringWith(array $options, array $with)
	{
		foreach ($options as $el) {
			$false = false;
			foreach ($with as $search) {
				if (strpos($el, $search) === false) {
					$false = true;
					continue;
				}
			}
			if (!$false) {
				return $el;
			}
		}
	}

	public function doAction()
	{
		$md5 = $this->request->getTrim('query');
		$key = $this->request->getTrim('key');
		$query = $this->update_statements[$key][$md5];
		//debug($this->update_statements);
		//debug($md5, $query); exit();
		if ($query) {
			$this->db->perform($query);
			$cache = new MemcacheArray(__CLASS__);
			$cache->clearCache();
			$this->request->redirect($this->linker->makeRelURL());
		}
	}

}
