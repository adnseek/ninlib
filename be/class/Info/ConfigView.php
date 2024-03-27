<?php

class ConfigView extends AppControllerBE
{

	public $file;
	protected $prefix = __CLASS__;
	protected $typeMap = [
		'string' => 'input',
		'boolean' => 'checkbox',
		'integer' => 'input',
	];

	public function __construct()
	{
		parent::__construct();
		$this->file = dirname(__FILE__) . '/../../../class/config.yaml';
		$this->file = str_replace('\\', '/', $this->file);
	}

	public function render()
	{
		$content = '';
		if (file_exists($this->file)) {
			$this->performAction($this->detectAction());
			$data = Spyc::YAMLLoad($this->file);
			//$content = getDebug($data);

			$f = new HTMLFormTable();
			$f->prefix($this->prefix);
			//$this->renderFormArray($f, '', $data);
			//debug($data, $this->file);
			foreach ($data as $class => $props) {
				//debug($props);
				$this->renderFormArray($f, $class, $props);
			}
			$f->prefix('');
			$f->hidden('action', 'save');
			$f->submit('Save');
			$f->debug = $_COOKIE['debug'];
			$content = $f;

			$content .= '<style>.tdlabel { width: 10em; } </style>';
		}
		return $content;
	}

	public function renderFormArray(HTMLFormTable $f, $class, array $data)
	{
		$f->fieldset($class);
		$desc = [];
		foreach ($data as $key => $val) {
			if (is_scalar($val)) {
				$desc[$class . '[' . $key . ']'] = [
					'label' => $key,
					'type' => $this->typeMap[gettype($val)],
					'value' => $val,
					'set0' => true,
					'optional' => true,
				];
			} elseif (is_array($val)) {
				/*$desc[$class.'['.$key.']'] = array(
					'type' => 'html',
					'code' => getDebug($val),
				);*/
				//foreach ($val as $key => $props) {
				debug($val);
				$this->renderFormArray($f, $class . '[' . $key . ']', $val);
				//}
			}
		}
		$f->desc = $desc;
		$f->showForm();
	}

	public function saveAction()
	{
		$data = $this->request->getArray($this->prefix);
		foreach ($data as $class => &$props) {
			foreach ($props as $key => &$val) {
				if ($val === "0") {
					$val = false;
				}
				if ($val === "1") {
					$val = true;
				}
			}
		}
		//debug($data);
		$yaml = Spyc::YAMLDump($data);
		//debug($yaml);
		file_put_contents($this->file, $yaml);
	}

}
