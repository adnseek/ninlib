<?php

use spidgorny\nadlib\HTTP\URL;

class slTableValue
{

	/**
	 * @var mixed
	 */
	public $value = null;

	/**
	 * @var array
	 */
	public $desc = [
		//		'hsc' => TRUE,
	];

	/**
	 * @var MySQL|DBLayer
	 */
	public $db;

	/**
	 * @var slTable
	 */
	public $caller;

	//public $SLTABLE_IMG_CHECK = '<img src="img/check.png">';
	public $SLTABLE_IMG_CHECK = '☑';
	//public $SLTABLE_IMG_CROSS = '<img src="img/uncheck.png">';
	public $SLTABLE_IMG_CROSS = '☐';

	public function __construct($value, array $desc = [])
	{
		if ($value instanceof slTableValue) {
			$value = $value->value;
			//debugster(array($value, $value->desc, '+', $desc, '=', (array)$value->desc + $desc));
			$desc = (array)$value->desc + $desc; // overwriting in a correct way
		}
		$this->value = $value;
		$this->desc += (array)$desc;
	}

	public function injectDB(DBInterface $db)
	{
		$this->db = $db;
	}

	/*	function render() {
			$value = $this->value;
			if (is_array($value)) {
				$value = t3lib_utility_Debug::viewArray($value);
			} else {
				if ($this->desc['hsc']) {
					$value = htmlspecialchars($value);
				}
			}
			return $value.' ('.$this->desc['type'].')';
		}
	*/

	public function __toString()
	{
		return $this->render();
	}

	public function render($col = null, array $row = [])
	{
		$content = $this->getCell($col, $this->value, $this->desc, $row);
		return $content;
	}

	public function getCell($col, $val, array $k, array $row)
	{
		$out = '';
		$type = isset($k['type']) ? $k['type'] : null;
		if (is_object($type)) {
			$type = get_class($type);
		}
		switch ($type) {
			case "select":
			case 'ajaxSingleChoice':
			case "selection":
				//debug($k + array('val' => $val));
				if ($val) {
					$what = ifsetor($k['title'], $col);
					$id = ifsetor($k['idField'], 'id');
					if (!isset($k['options'])) {
						if ($k['set']) {
							$list = trimExplode(',', $val);
							$out = [];
							foreach ($list as $val) {
								$out[] = $this->db->sqlFind($what, $k['from'], $id . " = '" . $val . "'", false);
							}
							$out = implode(', ', $out);
						} elseif ($k['from']) {
							$options = $this->db->fetchSelectQuery($k['from'], [$id => $val], '', $k['from'] . '.*, ' . $what);
							//debug($options, $k); exit();
							$whatAs = trimExplode('AS', $what);
							$whatAs = $whatAs[1] ? $whatAs[1] : $what;
							$options = ArrayPlus::create($options)
								->IDalize($id, true)
								->column($whatAs)
								->getData();
							$out = $options[$val];
						}
					} else {
						$options = $k['options'];
						$out = $options[$val];
					}
				} else {
					$out = "";
				}
				break;

			case "date":
				if ($val) {
					$out = date($k['format'] ?: 'Y-m-d H:i:s', $val);
				} else {
					$out = '';
				}
				break;

			case "gmdate":
				if ($val !== null) {
					if (is_numeric($val)) {
						$out = gmdate($k['format'] ?: 'Y-m-d', $val);
					} else {
						debug($col, 'is not long', $row);
					}
				} else {
					$out = '';
				}
				//$out .= '-'.var_export($val, TRUE);
				break;

			case 'hours':
				$out = $this->getHours($val);
				break;

			case "sqltime":
				if ($val) {
					$val = strtotime(substr($val, 0, 16)); // cut milliseconds
					$out = date($k['format'], $val);
				} else {
					$out = '';
				}
				break;

			case "sqldate":
				if ($val) {
					$val = new Date($val);
					$out = $val->format(ifsetor($k['format'], 'Y-m-d'));    // hours will not work
				} else {
					$out = '';
				}
				break;

			case "sqldatetime":
				if ($val) {
					$val = new Time($val);
					$out = $val->format(ifsetor($k['format'], 'Y-m-d H:i'));
				} else {
					$out = '';
				}
				break;

			case "file":
				$out = new HTMLTag('a', [
					'href' => $GLOBALS['uploadURL'] . $val,
				], $val);
				break;

			case "money":
				if (!is_numeric($val)) {
					debug($col, $val);
				}
				$out = number_format($val, 2, '.', '') . "&nbsp;&euro;";
				break;

			case "delete":
				$out = new HTMLTag('a', [
					'href' => "?perform[do]=delete&perform[table]={$this->caller->ID}&perform[id]=" . $row['id'],
				], "Del");
				break;

			case "datatable":
				//$out .= t3lib_utility_Debug::viewArray(array('col' => $col, 'val' => $val, 'desc' => $k));
//				$out = $k['prefix'];
//				$f = $this->caller->makeInstance(HTMLForm::class);
//				$f->prefix($this->prefixId);
//				$out .= $f->datatable($col, $val, $k, $details = true, $doDiv = TRUE, 'sltable', $data = 'test');
//				$out .= $k['append'];
				break;

			case 'link':
				$out = '<a href="' . $val . '" 
					target="' . ifsetor($k['target']) . '">' .
					ifsetor($k['text'], $val) . '</a>';
				break;

			case 'image':
				$out = new HTMLTag('img', [
						'src' => $k['prefix'] . $val,
					] + $k['more']);
				break;

			case "checkbox":
				if (ifsetor($k['tf'])) {
					$val = $val == 't';
				}
				if ($val) {
					$img = $this->SLTABLE_IMG_CHECK;
				} else {
					$img = $this->SLTABLE_IMG_CROSS;
				}
				if (ifsetor($row[$col . '.link'])) {
					$out = new HTMLTag('a', [
						'href' => $row[$col . '.link'],
					], $img, $k['no_hsc']);
				} else {
					$out = $img;
				}
				break;

			case "bool":
			case "boolean":
				if (intval($val)) {
					$out = ifsetor($k['true'], $this->SLTABLE_IMG_CHECK);
				} else {
					$out = ifsetor($k['false'], $this->SLTABLE_IMG_CROSS);
				}
				//$out .= t3lib_utility_Debug::viewArray(array('val' => $val, 'k' => $k, 'out' => $out));
				break;

			case "excel":
				$out = str_replace(',', '.', $val); // from excel?
				$out = number_format($out, 2, ',', '.');
				break;

			case 'check':
				$out = '<div style="text-align: center;">
					<input class="check" type="checkbox" disabled="" ' . ($val ? 'checked' : '') . ' />
				</div>';
				break;

			case "percent":
				$out = number_format($val * 100, 2, '.', '') . '&nbsp;%';
				break;

			case "bar":
				if (!is_null($val)) {
					$pb = new ProgressBar();
					if (isset($k['css'])) {
						$out = $pb->getImage($val * 100, $k['css']);
					} else {
						$out = $pb->getImage($val * 100);
					}
				}
				break;

			case "callback":
				$out = call_user_func($k['callback'], $val, $k, $row);
				break;

			case "instance":
				$obj = is_object($k['class']) ? $k['class'] : new $k['class']($val);
				if (ifsetor($k['method']) && method_exists($obj, $k['method'])) {
					$out = call_user_func([$obj, $k['method']]);
				} else {
					$out = $obj . '';
				}
				break;

			case "singleton":
				if ($val) {
					if (ifsetor($k['csv'])) {
						$parts = trimExplode(',', $val);
						$obj = [];
						foreach ($parts as $id) {
							$obj[] = is_object($k['class'])
								? $k['class']
								: $k['class']::getInstance($id);
						}
						$out = implode(', ', $obj);
					} else {
						$obj = is_object($k['class'])
							? $k['class']
							: $k['class']::getInstance($val);
						$out = $obj . '';
					}
				}
				break;

			case "singleLink":
				$out = new HTMLTag('a', [
					'href' => new URL($k['link'] . $row[$k['idField']]),
				], $val ?: $k['text']);
				break;

			case 'HTMLFormDatePicker':
				//$val = strtotime($val);
				//$out = date($k['type']->format, $val);
				if ($val) {
					$val = new Date($val);
					$out = $val->format($k['type']->format);
				}
				break;

			case "default":
				$out = isset($k['text']) ? $k['text'] : 'Provide text property';
				break;
			/** @noinspection PhpMissingBreakStatementInspection */
			case "textarea":
				$val = nl2br($val);
			//break; // FALL DOWN!
			default:
				if ($val instanceof slTableValue) {
					$out = $val->render();
				} else {
					//t3lib_div::debug($k);
					if (isset($k['hsc']) && $k['hsc'] && !($val instanceof HtmlString)) {
						$val = htmlspecialchars($val);
					}
					if (ifsetor($k['explode'])) {
						$val = trimExplode($k['explode'], $val);
					}
					if (isset($k['nl2br']) && $k['nl2br']) {
						$val = nl2br(htmlspecialchars($val));    // escape it (!)
						$k['no_hsc'] = true;    // for below
					}
					if (isset($k['no_hsc']) && $k['no_hsc']) {
						$out = $val;
					} elseif ($val instanceof HtmlString) {
						$out = $val . '';
					} elseif ($val instanceof HTMLTag) {
						$out = $val . '';
					} elseif ($val instanceof HTMLDate) {
						$out = $val . '';
					} elseif ($val instanceof HTMLForm) {
						$out = $val->getContent() . '';   // to avoid calling getName()
					} elseif (is_object($val)) {
						if (ifsetor($k['call'])) {
							$out = $val->$k['call']();
						} elseif (method_exists($val, 'getName')) {
							$out = $val->getName();
						} elseif (method_exists($val, '__toString')) {
							$out = $val->__toString();
						} else {
							$out = '[' . get_class($val) . ']';
						}
					} elseif (is_array($val)) {
						if (is_assoc($val)) {
							$out = json_encode($val, defined('JSON_PRETTY_PRINT')
								? JSON_PRETTY_PRINT
								: null);
						} else {
							$out = '[' . implode(', ', $val) . ']';
						}
						$out = htmlspecialchars($out);
					} elseif ($out == '' && ifsetor($k['default'])) {
						$out = htmlspecialchars($k['default']);
					} else {
						$out = htmlspecialchars($val ?? '');
					}
				}
				break;
		}
		if (isset($k['wrap']) && $k['wrap']) {
			$wrap = $k['wrap'] instanceof Wrap ? $k['wrap'] : new Wrap($k['wrap']);
			$out = $wrap->wrap($out);
		}
		if (isset($k['link']) && $k['link']) {
			$link = $k['link'];
			foreach ($row as $key => $rowVal) {
				$link = str_replace('###' . strtoupper($key) . '###', $rowVal, $link);
				$link = str_replace('{{' . strtolower($key) . '}}', $rowVal, $link);
				$link = str_replace('%7B%7B' . strtolower($key) . '%7D%7D', $rowVal, $link);
			}
			if (isset($k['value'])) {
				$link = str_replace('###VALUE###', $val ?: $k['value'], $link);
			}
			$link = str_replace('###ID###', $out, $link);
			$out = '<a href="' . $link . '">' . $out . '</a>';
		}
		if (isset($k['round']) && $out) {
			$out = number_format($out, $k['round'], '.', '');
		}
		return $out;
	}

	public static function getHours($timestamp)
	{
		if ($timestamp) {
			//return gmdate('H:i', $timestamp);
			$whole = floor($timestamp / (60 * 60));
			$whole = str_pad($whole, 2, '0', STR_PAD_LEFT);

			$rest = ($timestamp / 60) % 60;
			$rest = str_pad($rest, 2, '0', STR_PAD_LEFT);
			return $whole . ':' . $rest;
		}
		return null;
	}

}
