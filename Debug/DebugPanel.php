<?php

/**
 * Inspired by debugster http://typo3.org/documentation/document-library/extension-manuals/beko_debugster/1.4.1/view/1/1/
 * Usage:
 * $GLOBALS['profiler'] = new TaylorProfiler(TRUE);
 * ...
 * DebugPanel::getInstance()->addPanel('Request', new Request());
 * ...
 * $dp = DebugPanel::getInstance();
 * $dp->addPanel('TaylorProfiler', new HtmlString($GLOBALS['profiler']->printTimers(TRUE)));
 * DebugPanel::getInstance()->addPanel('System Variables', array(
 * 'REQUEST' => $_REQUEST,
 * 'GET' => $_GET,
 * 'POST' => $_POST,
 * 'FILES' => $_FILES,
 * 'COOKIE' => $_COOKIE,
 * 'SESSION' => $_SESSION,
 * 'GLOBALS' => $_GLOBALS,
 * ));
 * DebugPanel::getInstance()->addPanel('Test', array(
 * 'string' => 'Hello World!',
 * 'int' => 10,
 * 'double' => 3.1428,
 * 'null' => null,
 * 'bool' => true,
 * 'array' => array(0, 1, 2, 3, 'slawa'),
 * 'panel' => array('info' => array('position' => 'fixed')),
 * 'someHTML' => new HtmlString('<big>Hi</big>I am an <span style="text-decoration: small-caps;">HTML</span> string.'),
 * ));
 * DebugPanel::getInstance()->addPanel('Server Stat', new HtmlString(new ServerStat().''));
 *
 */
class DebugPanel
{
	protected static $instance;
	public $header = 'h6';
	protected $name = 'DebugPanel';
	protected $content = '';
	protected $panels = [];

	protected function __construct($name = null, $content = null)
	{
		if ($name) {
			$params = $this->getVarParams($content);
			$name .= ' (' . $params['typeName'] . ')';
			$this->name = $name;
		}
		if (is_array($content)) {
			$this->content = $this->viewArray($content);
		} elseif (is_object($content) && !($content instanceof HtmlString)) {
			$this->content = $this->viewArray(get_object_vars($content));
		} else {
			$this->content = $content instanceof HtmlString ? $content : htmlspecialchars($content);
		}
	}

	public function getVarParams($var)
	{
		$params = [];
		$type = gettype($var);
		$params['type'] = $type;
		if (is_array($var)) {
			$params['size'] = count($var);
			$params['typeName'] = $type . '[' . count($var) . ']';
		} elseif ($type == 'object') {
			$params['class'] = get_class($var);
			$params['hash'] = spl_object_hash($var);
			//$params['methods'] = get_class_methods(get_class($var));
			$params['typeName'] = $params['class'];
			if ($extends = get_parent_class(get_class($var))) {
				$params['extends'] = $extends;
				$params['typeName'] .= ':' . $extends;
			}
		} elseif ($type == 'NULL') {
			$params['typeName'] = $type;
		} elseif ($type == 'string') {
			$params['length'] = strlen($var);
			$params['typeName'] = $type . '(' . strlen($var) . ')';
		} elseif ($type == 'boolen') {
			$params['typeName'] = $type;
		} else {
			$params['length'] = strlen($var);
			$params['typeName'] = $type . '(' . strlen($var) . ')';
		}
		return $params;
	}

	public function viewArray($array)
	{
		$table = [];
		foreach ($array as $key => $val) {
			$row = [];
			$row['key'] = $key;
			$row += $this->getVarParams($val);
			$type = $row['type'];
			//$row['typeName'] = '<div class="'.$type.'">'.$row['typeName'].'</div>';
			$row['typeName'] = new HTMLTag('td', ['class' => $type], $row['typeName']);
			unset($row['type']);
			unset($row['size']);
			unset($row['length']);
			unset($row['class']);
			unset($row['hash']);
			unset($row['extends']);
			if (is_array($val) || is_object($val) || is_null($val)) {
				$row['value'] = new HTMLTag('td', ['class' => $type], $val ? new DebugPanel($key, $val) : '', true);
			} else {
				$row['value'] = new HTMLTag('td', ['class' => $type . ' overflow'], $val);
			}
			$table[] = $row;
		}
		return new slTable($table, 'class="view_array array"');
	}

	public static function getInstance()
	{
		if (!self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function render()
	{
		$content = $this->getHeader();
		$this->header = 'h5';
		$content .= '<div class="DebugPanel">' . $this->__toString() . '</div>';
		return $content;
	}

	public function getHeader()
	{
		$content = '<link rel="stylesheet" type="text/css" href="css/debugPanel.css">';
		//$content .= '<script src="js/jquery-1.3.2.min.js"></script>';
		//$content .= '<script src="js/jquery-ui-1.7.2.custom.min.js"></script>';
		//$content .= '<script src="js/jquery.cookie.js"></script>';
		$content .= '<script src="js/jquery.json-2.2.min.js"></script>';
		$content .= '<script src="js/debugPanel.js"></script>';
		return $content;
	}

	public function __toString()
	{
		$h6 = $this->header;
		$content = '<div class="panel">
		<' . $h6 . ' class="' . gettype($this->content) . '">' . $this->name . '</' . $h6 . '>';
		if ($this->content || $this->panels) {
			$content .= '<div class="content">';
			foreach ($this->panels as $panel) {
				$content .= $panel;
			}
			$content .= $this->content . '</div>';
		}
		$content .= '</div>';
		return $content;
	}

	public function addPanel($name, $content)
	{
		$dp = new DebugPanel($name, $content);
		$this->panels[$name] = $dp;
	}

	public function __destruct()
	{
		//echo $this->render();
	}

}
