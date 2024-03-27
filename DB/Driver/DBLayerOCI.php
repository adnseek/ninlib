<?php

class DBLayerOCI extends DBLayer implements DBInterface
{
	public $connection = null;
	public $COUNTQUERIES = 0;
	public $LAST_PERFORM_RESULT;
	public $LOG;
	public $debug = false;
	public $debugOnce = false;
	public $is_connected = false;

	public function __construct($tns, $user, $pass)
	{
		$this->connect($tns, $user, $pass);
		//debug('<div class="error">OCI CONNECT</div>');
	}

	public function __toString()
	{
		return '[Object of type dbLayerOCI]';
	}

	/**
	 * @param string $tns
	 * @param string $user
	 * @param string $pass
	 * @param string $host - unused, for declaration consistency
	 * @return bool|null|resource
	 */
	public function connect($tns = null, $user = null, $pass = null, $host = 'localhost')
	{
		$this->connection = oci_connect($user, $pass, $tns);
		if (!$this->connection) {
			print('Error in Oracle library: connection failed. Reason: ' . getDebug(oci_error($this->connection)) . BR);
			return null;
		}
		return $this->connection;
	}

	public function getConnection()
	{
		return $this->connection;
	}

	public function disconnect()
	{
		oci_close($this->connection);
	}

	public function insertFields()
	{
		return [];
	}

	public function updateFields()
	{
		return [];
	}

	public function performOCI($query, $canprint = true, $try = false)
	{
		if (!$this->connection) {
			print('Error in Oracle library: no connection. Query: ' . $query . BR);
			return null;
		}
		$this->COUNTQUERIES++;
		if ($this->debugOnce || $this->debug) {
			//debug($query);
		}

		list($time1['usec'], $time1['sec']) = explode(" ", microtime());
		$time1['float'] = (float)$time1['usec'] + (float)$time1['sec'];

		$this->LAST_PERFORM_RESULT = oci_parse($this->connection, $query);
		$error = oci_error();
		if ($error) {
			print('Oracle error ' . $error['code'] . ': ' . $error['message'] . ' while doing ' . $query . BR);
		}
		if ($try) {
			@oci_execute($this->LAST_PERFORM_RESULT, OCI_DEFAULT);
			//debug($this->LAST_PERFORM_RESULT); exit();
			//debug(oci_error($this->LAST_PERFORM_RESULT)); exit();
			if (oci_error($this->LAST_PERFORM_RESULT)) {
				$this->LAST_PERFORM_RESULT = null;
			}
		} else {
			oci_execute($this->LAST_PERFORM_RESULT, OCI_DEFAULT);
			$error = oci_error($this->LAST_PERFORM_RESULT);
			if ($error) {
				print('Oracle error ' . $error['code'] . ': ' . $error['message'] . ' while doing ' . $query . BR);
			}
		}

		list($time2['usec'], $time2['sec']) = explode(" ", microtime());
		$time2['float'] = (float)$time2['usec'] + (float)$time2['sec'];

		$numRows = $this->numRows($this->LAST_PERFORM_RESULT);
		if ($this->debugOnce || $this->debug) {
			debug([$query]);
			$this->debugOnce = false;
		}
		$elapsed = number_format($time2['float'] - $time1['float'], 3);
		$debug = debug_backtrace();
		$deb = '';
		foreach ($debug as $i => $row) {
			if ($i > 1) {
				unset($row['object']);
				$deb .= implode(', ', $row);
				$deb .= "\n";
			}
		}
		if ($this->LOG[$query]) {
			$a = $this->LOG[$query];
			$this->LOG[$query] = [
				'timestamp' => '',
				'file' => $debug[1]['file'],
				'function' => $debug[1]['function'],
				'line' => $debug[1]['line'],
				'title' => $deb,
				'query' => $query,
				'results' => $numRows,
				'elapsed' => $a['elapsed'],
				'count' => 1 + $a['count'],
				'total' => $elapsed + $a['total'],
			];
		} else {
			$this->LOG[$query] = [
				'timestamp' => date('H:i:s'),
				'file' => $debug[1]['file'],
				'function' => $debug[1]['function'],
				'line' => $debug[1]['line'],
				'title' => $deb,
				'query' => $query,
				'results' => $numRows,
				'elapsed' => $elapsed,
				'count' => 1,
				'total' => $elapsed,
			];
		}
		return $this->LAST_PERFORM_RESULT;
	}

	public function done($result)
	{
		oci_free_statement($result);
	}

	/*	function transaction() {
			// everything is a transaction in oracle
			ora_commitoff($this->CONNECTION);
		}
	*/
	public function commit()
	{
		oci_commit($this->connection);
	}

	/*
		function rollback() {
			ora_rollback($this->CONNECTION);
		}
	*/
	public function quoteSQL($value, $more = [])
	{
		if ($value == "CURRENT_TIMESTAMP") {
			return $value;
		} elseif ($value === null) {
			return 'NULL';
		} elseif ($value === true) {
			return "'t'";
		} elseif ($value === false) {
			return "'f'";
		} elseif ($more['asis']) {
			return $value;
		} elseif (is_numeric($value)) {
			return $value;
		} elseif ($value == '') {
			return "NULL";
		} else {
			return "'" . pg_escape_string($value) . "'";
		}
	}

	public function fetchAll($result, $key = null)
	{
		$ret = [];
		while (($row = $this->fetchAssoc($result)) !== false) {
			$ret[] = $row;
		}
		return $ret;
	}

	public function fetchAssoc($result)
	{
		$array = oci_fetch_array($result, OCI_RETURN_NULLS | OCI_ASSOC);
		return $array;
	}

	public function numRowsFast($result)
	{
		return oci_num_rows($result);
	}

	public function numRows($result = null)
	{
		$i = 0;
		while (($row = $this->fetchAssoc($result)) !== false) {
			$i++;
		}
		return $i;
	}

	public function to_date($timestamp)
	{
		$content = "to_date('" . date('Y-m-d H:i', $timestamp) . "', 'yyyy-mm-dd hh24:mi')";
		return $content;
	}

	public function to_timestamp($value)
	{
		return strtotime($value);
	}

	public function filterFields()
	{
		return [];
	}

}
