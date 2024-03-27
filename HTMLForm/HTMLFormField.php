<?php

/**
 * Class HTMLFormField
 * This represents an element of HTMLFormTable desc array
 * Do not confuse it with HTMLFormType and it's descendants.
 * @property $elementID string
 */
class HTMLFormField implements ArrayAccess, HTMLFormFieldInterface
{

	/**
	 * All different desc parameters for the form element.
	 * Temporary solution while we transition from array assoc
	 * to the HTMLFormField with specific members
	 * @var array
	 */
	public $data = [];

	/**
	 * @var string
	 */
	public $fieldName;

	/**
	 * @var HTMLForm
	 */
	public $form;

	/**
	 * Filled when render()
	 * @var string
	 */
	protected $content;

	public function __construct(array $desc, $fieldName = null)
	{
		$this->data = $desc;
		if ($fieldName) {
			$this->setField($fieldName);
		}
		$this->form = new HTMLForm();
	}

	public function setField($fieldName)
	{
		$this->fieldName = $fieldName;
	}

	public function offsetExists($offset): bool
	{
		return isset($this->data[$offset]);
	}

	/**
	 * ifsetor() here will not work:
	 * Only variable references should be returned by reference
	 * @param mixed $offset
	 * @return mixed
	 */
	public function &offsetGet($offset): mixed
	{
		$ref = $this->data[$offset] ?? null;
		return $ref;
	}

	public function offsetSet($offset, $value): void
	{
		if (is_null($offset)) {
			$this->data[] = $value;
		} else {
			$this->data[$offset] = $value;
		}
	}

	public function offsetUnset($offset): void
	{
		unset($this->data[$offset]);
	}

	public function __get($name)
	{
		return ifsetor($this->data[$name], null);
	}

	public function __isset($name)
	{
		return ifsetor($this->data[$name], null);
	}

	public function __set($name, $value)
	{
		$this->data[$name] = $value;
	}

	public function isOptional()
	{
		return !$this->isObligatory();
	}

	public function isObligatory()
	{
		$type = $this->getTypeString();
		return !ifsetor($this->data['optional']) &&
			!in_array($type, ['check', 'checkbox', 'submit']);
	}

	public function getTypeString()
	{
		$type = ifsetor($this->data['type']);
		if (is_null($type)) {
			return null;
		}
		return is_string($type) ? $type : get_class($type);
	}

	public function setForm(HTMLForm $form)
	{
		$this->form = $form;
	}

	public function setValue($value)
	{
		$this->data['value'] = $value;
	}

	public function render()
	{
		$fieldName = $this->fieldName;
		$desc = $this;
		$fieldValue = $this['value'];
//		debug($fieldValue);
		if ($desc['prefix']) {
			$this->form->text($desc['prefix']);
		}

//		debug($desc['id']);
		if (!empty($desc['id'])) {
			$elementID = $desc['id'];
		} elseif (!empty($desc['more']['id'])) {
			$elementID = $desc['more']['id'];
		} else {
			$elementID = $this->getID($this->fieldName);
			$desc['id'] = $elementID;
		}
		$this['elementID'] = $elementID;
//		debug($elementID);

		$type = ifsetor($desc['type']);
		if ($type instanceof HTMLFormType) {
			/* @var $type HTMLFormType */
			$type->setField($fieldName);
			$type->setForm($this->form);
			if (ifsetor($desc['value'])) {
				$type->setValue($desc['value']);
			}
			if (ifsetor($desc['jsParams'])) {
				$type->jsParams = $desc['jsParams'] ? $desc['jsParams'] : [];
			}
			$type->desc = $desc;
			$index = Index::getInstance();
			$this->form->stdout .= $index->s($type->render());
		} elseif ($type instanceof HTMLFormCollection) {
			/** @var $type HTMLFormCollection */
			$type->setField($fieldName);
			$type->setForm($this->form);
			$type->setValue($desc['value']);
			$type->setDesc($desc);
			$this->form->stdout .= $type->renderHTMLForm();
		} else {
			$this->switchTypeRaw($type, $fieldValue, $fieldName);
		}
		$this->content = $this->form->stdout;
		return $this->content;
	}

	public function getID($from)
	{
		if (is_array($from)) {
			$elementID = 'id-' . implode('-', $from);
		} else {
			$elementID = 'id-' . $from;
		}
		if (!$elementID) {
			$elementID = uniqid('id-', true);
		}
		return $elementID;
	}

	/**
	 * @param string $type
	 * @param mixed $fieldValue
	 * @param string $fieldName
	 * @throws Exception
	 */
	private function switchTypeRaw($type, $fieldValue, $fieldName)
	{
		$desc = $this;
		switch ($type) {
			case "string":
				$this->form->text($fieldValue);
				break;
			case "textarea":
				$this->form->textarea(
					$fieldName,
					$fieldValue,
					(is_array(ifsetor($desc['more']))
						? HTMLForm::getAttrHTML($desc['more'])
						: $desc['more']
					) .
					($desc['id'] ? ' id="' . $desc['id'] . '"' : '') .
					(ifsetor($desc['disabled']) ? ' disabled="1"' : '') .
					(ifsetor($desc['class']) ? ' class="' . htmlspecialchars($desc['class'], ENT_QUOTES) . '"' : '')
				);
				break;
			case "date":
				//t3lib_div::debug(array($fieldName, $fieldValue));
				$this->form->date($fieldName, $fieldValue, $desc->getArray());
				break;
			case "datepopup":
				$this->form->datepopup($fieldName, $fieldValue);
				break;
			case "datepopup2":
				$this->form->datepopup2($fieldName, $fieldValue, ifsetor($desc['plusConfig']), $desc->getArray());
				break;

			case "money":
				$this->form->money($fieldName, $fieldValue, $desc->getArray());
				break;

			case "select":
			case "selection":
				$this->form->selection(
					$fieldName,
					$desc['options'],
					ifsetor($fieldValue, ifsetor($desc['default'])),
					$desc['autosubmit'] ?? null,
					[],    // more
					$desc['multiple'] ?? null,
					$desc->getArray()
				);
				break;
			case "file":
				$this->form->file($fieldName, $desc->getArray());
				break;

			case "password":
				$this->form->password($fieldName, $fieldValue, $desc->getArray());
				break;

			case "check":
			case "checkbox":
				if (ifsetor($desc['set0'])) {
					$this->form->hidden($fieldName, 0);
				}
				$elementID = $this['elementID'];
				$more = ifsetor($desc['more'], []) + ['id' => $elementID];
				if (ifsetor($desc['postgresql'])) {
					$fieldValue = $fieldValue === 't';
				}
				$this->form->check($fieldName, ifsetor($desc['post-value'], 1), $fieldValue, /*$desc['postLabel'], $desc['urlValue'], '', false,*/
					$more, ifsetor($desc['autoSubmit']), $desc->getArray());
				break;

			case "time":
				$this->form->time($fieldName, $fieldValue, $desc['unlimited']);
				break;

			case "hidden":
			case "hide":
				$this->form->hidden($fieldName, $fieldValue, $desc['id']
					? ['id' => $desc['id']]
					: []);
				break;
			case 'hiddenArray':
				$name = is_array($fieldName) ? end($fieldName) : $fieldName;
				$this->form->formHideArray([$name => $fieldValue]);
				break;

			case 'html':
				$this->form->text($desc['code']);
				break;

				case 'tree':
				$this->form->tree($fieldName, $desc['tree'], $fieldValue);
				break;

			case 'popuptree':
				$this->form->popuptree($fieldName, $desc['value'], $desc['valueName'], $desc->getArray());
				break;
			case 'submit':
//				llog('submit', $desc);
				$desc['name'] = ifsetor($desc['name'], $fieldName);
				//debug($desc);
				$more = (is_array(ifsetor($desc->data['more']))
						? $desc->data['more'] : []) + [
						'id' => $desc->data['id']
						];
				$this->form->submit($desc['value'], $more);
				break;

			case 'captcha':
				$this->form->captcha($fieldName, $fieldValue, $desc->getArray());
				break;
			case 'recaptcha':
				$this->form->recaptcha($desc->getArray() + [
						'name' => $this->form->getName($fieldName, '', true)
					]);
				break;
			case 'recaptchaAjax':
				$this->form->recaptchaAjax(
					$desc->getArray() + [
						'name' => $this->form->getName($fieldName, '', true)
					]);
				break;
			case 'datatable':
				$this->form->datatable($fieldName, $fieldValue, $desc, false, $doDiv = true, 'htmlftable');
				break;
			case 'ajaxSingleChoice':
				$this->form->ajaxSingleChoice($fieldName, $fieldValue, $desc->getArray());
				break;
			case 'set':
				$this->form->set($fieldName, $fieldValue, $desc->getArray());
				break;
			case 'keyset':
				$this->form->keyset($fieldName, $fieldValue, $desc->getArray());
				break;
			case 'checkarray':
				if (!is_array($fieldValue)) {
					debug($fieldName, $fieldValue, $desc->getArray());
				}
				$this->form->checkarray($fieldName, $desc['set'], $fieldValue, $desc->getArray());
				break;
			case 'radioset':
				$this->form->radioset($fieldName, $fieldValue, $desc->getArray());
				break;
			case 'radiolist':
				$this->form->radioArray($fieldName, $desc['options'], $fieldValue);
				break;
			case 'combo':
				$this->form->combo($fieldName, $desc->getArray());
				break;
			case 'button':
				$this->form->button($desc['innerHTML'], $desc['more'] ?: []);
				break;
			case 'fieldset':
				//$this->fieldset($desc['label']);	// it only sets the global fieldset name
				$this->form->stdout .= '<fieldset>
					<legend>' . htmlspecialchars($desc['label']) . '</legend>';
				break;
			case '/fieldset':
				$this->form->stdout .= '</fieldset>';
				break;
			case 'tfieldset':
				if (!$desc['close']) {
					$this->form->stdout .= '</td></tr></table>
						<fieldset><legend>' . $desc['legend'] . '</legend>
						<table><tr><td>';
				} else {
					$this->form->stdout .= '</td></tr></table>
						</fieldset>
						<table><tr><td>';
				}
				break;
			/** @noinspection PhpMissingBreakStatementInspection */
			case 'email':
				$type = 'email';
			//break;	// intentional
			case "input":
			case "text":
			default:
				$type = $type ?: 'text';
				//$this->text(htmlspecialchars($desc['more']));
//				debug($desc);
				$more = ifsetor($desc['more']);
				if ($more && !is_array($more)) {
					$more = HTMLTag::parseAttributes($more);
				}
				if (ifsetor($desc['id'])) {
					$more['id'] = $desc['id'];
				}
				if (ifsetor($desc['size'])) {
					$more['size'] = $desc['size'];
				}
				if (ifsetor($desc['readonly'])) {
					$more['readonly'] = 'readonly';
				}
				if (ifsetor($desc['disabled'])) {
					$more['disabled'] = 'disabled';
				}
				if ($desc->isObligatory()) {
					$more['required'] = "required";
				}
				if (ifsetor($desc['autofocus'])) {
					$more['autofocus'] = 'autofocus';
				}
				$this->form->input($fieldName, $fieldValue, $more, $type === 'input' ? 'text' : $type,
					ifsetor($desc['class'],
						is_array(ifsetor($desc['more']))
							? ifsetor($desc['more']['class'])
							: null
					)
				);
				//debug($desc, $desc->isObligatory(), $desc->getTypeString());
				break;
		}
	}

	public function getArray()
	{
		return $this->data;
	}

	public function getContent()
	{
		return $this->content;
	}

	public function isCheckbox()
	{
		return $this->getTypeString() === 'checkbox';
	}

	public function setOptional($is = true)
	{
		$this->data['optional'] = $is;
	}

}
