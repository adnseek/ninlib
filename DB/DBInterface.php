<?php

/**
 * Interface DBInterface
 * @mixin SQLBuilder
 * @method fetchSelectQuery($table, $where = [], $order = '', $addFields = '', $idField = null)
 * @method fetchOneSelectQuery($table, $where = [], $order = '', $selectPlus = '')
 * @method describeView($viewName)
 * @method fetchAllSelectQuery($table, array $where, $order = '', $selectPlus = '', $key = null)
 * @method getFirstValue($query)
 * @method runUpdateQuery($table, array $columns, array $where, $orderBy = '')
 * @method performWithParams($query, $params)
 * @method getConnection();
 * @method getViews();
 * @method getSelectQuery($table, array $where = [], $order = '', $addSelect = '')
 * @method runSelectQuery($table, array $where = [], $order = '', $addSelect = '')
 * @method getInsertQuery($table, array $data);
 * @method getDeleteQuery($table, array $where = [], $what = '');
 * @method getUpdateQuery($table, array $set, array $where);
 * @method runInsertQuery($table, array $data);
 * @method runInsertUpdateQuery($table, array $fields, array $where, array $insert = []);
 * @method runDeleteQuery($table, array $where);
 */
interface DBInterface
{

	public function perform($query, array $params = []);

	public function numRows($res = null);

	public function affectedRows($res = null);

	public function getTables();

	public function lastInsertID($res, $table = null);

	public function free($res);

	public function quoteKey($key);

	public function quoteKeys(array $keys);

	public function escapeBool($value);

	public function fetchAssoc($res);

	public function transaction();

	public function commit();

	public function rollback();

	public function getScheme();

	public function getTablesEx();

	public function getTableColumnsEx($table);

	public function getIndexesFrom($table);

	public function dataSeek($resource, $index);

	public function escape($string);

	public function fetchAll($res_or_query, $index_by_key = null);

	public function isConnected();

	public function quoteSQL($value, $key = null);

	public function clearQueryLog();

	public function getLastQuery();

	/** @return array */
	public function getInfo();

	/** @return string */
	public function getDSN();

	public function getDatabaseName();

	public function getVersion();

}
