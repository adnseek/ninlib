<?php

class SQLNow extends AsIs
{

	public function __construct()
	{
		parent::__construct('');
	}

	public function __toString()
	{
		if (!$this->db) {
			debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			trigger_error(__CLASS__ . ' has no $db', E_USER_ERROR);
		}
		$map = [
			'sqlite' => "datetime('now')",
			'mysql' => 'now()',
			'mysqli' => 'now()',
			'ms' => 'GetDate()',
			'postgresql' => 'now()',
			'pg' => 'now()',
			DBPlacebo::class . '://' => 'now()'
		];
		$schema = $this->db->getScheme();
		if (!isset($map[$schema])) {
			trigger_error('[' . $schema . '] is not supported by SQLNow', E_USER_ERROR);
		}
		$content = $map[$schema] ?: end($map);
		return $content;    // should not be quoted
	}

}
