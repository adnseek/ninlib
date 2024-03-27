<?php

use spidgorny\nadlib\HTTP\URL;

/**
 * Class Scaffold
 * @deprecated - use HTMLFormProcessor if you only need the edit form
 */
abstract class Scaffold extends AppControllerBE
{

	/**
	 * OODBase based model class to modify database.
	 *
	 * @var OODBase
	 */
	public $model;
	/**
	 * Either from the <FORM> or default from DB?
	 * @var array
	 */
	public $data = [];
	/**
	 * @var string
	 */
	protected $table = 'sometable in Scaffold';
	/** @var HTMLFormTable */
	protected $form;
	/**
	 * Name of the form fields: scaffold[asd]
	 */
	protected $formPrefix = 'scaffold';
	/**
	 * @var array
	 * @deprecated    - why? Use Collection instead?
	 */
	protected $thes = [];
	/**
	 * Button label
	 * @var string
	 */
	protected $addButton = 'Add';
	/**
	 * Button label
	 * @var string
	 */
	protected $updateButton = 'Save';
	/**
	 * Default function to display.
	 * showForm
	 * showEdit
	 * add
	 * edit
	 * @var string
	 */
	protected $action = '';
	/**
	 * extra attributes for the form like onSubmit
	 * @var string
	 */
	protected $formMore = '';
	/**
	 * edited element
	 * @var int
	 */
	protected $id;
	protected $editIcon = '<img src="../../img/stock-edit-16.png" alt="Edit"/>';

	/**
	 * @var Request
	 */
	protected $subRequest;

	public function __construct()
	{
		parent::__construct();
		$this->translateThes();
		$this->addButton = __($this->addButton);
		$this->updateButton = __($this->updateButton);
		$this->action = $this->request->getCoalesce('action', $this->action);
		$this->id = $this->request->getInt($this->table . '_id');
		if (!$this->id) {
			$this->id = $this->request->getInt($this->table . '.id'); // NON AJAX POST
		}
		if (!$this->id) {
			// don't do it. It can be ID of anything (parent record)
			//$this->id = $this->request->getInt('id');
		}

		$this->subRequest = $this->request->getSubRequest($this->formPrefix);
		$this->setModel();    // uses $this->id

		$this->form = new HTMLFormTable();
		//debug($this->request->isSubmit(), $this->formPrefix, $this->request->getArray($this->formPrefix));
		if ($this->request->isSubmit()) {
			$this->data = $this->subRequest->getAll();
		} else {
			$this->data = $this->model->data;
		}
//		debug($this->data);
		$this->form->desc = $this->getDesc((array)$this->data);
		//debug($this->form->desc);
		nodebug([
			'id' => $this->id,
			'isSubmit' => $this->request->isSubmit(),
			'formPrefix' => $this->formPrefix,
			'data' => $this->data,
			'model' => $this->model]);
	}

	/**
	 * @deprecated
	 */
	public function translateThes()
	{
		// translate thes
		foreach ($this->thes as $key => &$trans) {
			if (is_string($trans) && $trans) {
				$trans = __($trans);
			} elseif (is_array($trans) && ifsetor($trans['name'])) {
				$trans['name'] = __($trans['name']);
			}
		}
	}

	/**
	 * Sets $this->model to something. Can use $this->id for editing mode
	 */
	abstract public function setModel();

	/**
	 * Needs to implement data into the desc internally!!!
	 * Please use HTMLFormTable::fillValues()
	 * @param array $data - the source data of the edited record, if in edit more
	 * @return array
	 */
	protected function getDesc(array $data = [])
	{
		$desc = [
			'name' => [
				'label' => 'Name',
			],
		];
		$this->form->desc = $desc;
		$this->form->fill($data);
		return $this->form->desc;
	}

	public function render()
	{
		$content = [];
//		debug($this->action);
		switch ($this->action) {
			case 'showForm':
				$content[] = $this->showForm();
				break;
			case 'showEdit':
				$content[] = $this->showEditForm();
				break;
			case 'add':
			case 'update':
				$content[] = $this->showPerform();
				break;
			default:    // view table
				if (method_exists($this, $this->action . 'Action')) {
					$content[] = call_user_func([$this, $this->action . 'Action']);
				} else {
					$content[] = $this->showDefault();
				}
				break;
		}
		return $content;
	}

	public function showForm()
	{
		if ($this->action == 'showEdit' || $this->action == 'update') {
			$f = $this->showEditForm();
		} else {
			$f = $this->getForm();
			$f->prefix('');
			$f->submit($this->addButton, [
				'class' => 'btn btn-primary',
			]);
		}
		return $f;
	}

	/**
	 * Will be called by showForm() if the action is showEdit
	 * @return HTMLFormTable
	 */
	protected function showEditForm()
	{
		$override = [
			$this->table . '.id' => $this->id,
		];

		/*		if ($this->desc['submit']) {
					$this->desc['submit']['value'] = $this->updateButton;
				}
		*/
		$f = $this->getForm('update');
		$f->prefix('');
		foreach ($override as $key => $val) {
			$f->hidden($key, $val);
		}
		$f->button('<span class="glyphicon glyphicon-floppy-disk"></span> ' . $this->updateButton, [
			'type' => 'submit',
			'class' => 'btn btn-primary',
		]);
		return $f;
	}

	/**
	 * Default is add action, override to update
	 *
	 * @param string $action
	 * @return HTMLFormTable
	 */
	protected function getForm($action = 'add')
	{
		$this->form->method('POST');
		$this->form->hidden('c', get_class($this));
		$this->form->hidden('pageType', get_class($this));
		$this->form->hidden('action', $action);
		//$this->form->hidden('ajax', TRUE);        // add this to getDesc()
		$this->form->prefix($this->formPrefix);
		$this->form->showForm();
		//$this->form->submit($this->addButton);    // because it's used for edit
		$this->form->formMore = $this->formMore;
		return $this->form;
	}

	/**
	 * Return nothing or false to indicate success.
	 * $this->insertRecord should return nothing?!?
	 *
	 * @return string[]
	 * @throws Exception
	 */
	public function showPerform()
	{
		$content = [];
		$v = new HTMLFormValidate($this->form);
		if ($v->validate()) {
			try {
				switch ($this->action) {
					case 'add':
						$content[] = $this->insertRecord($this->data);
						break;
					case 'update':
						$content[] = $this->updateRecord($this->data);
						break;
					default:
						throw new Exception(__METHOD__ . ' has no action');
				}
			} catch (DatabaseException $e) {
				$content[] = '<p class="error ui-state-error">We were unable to perform the operation because "' . $e->getMessage() . '". Please check your form fields and retry. Please let us know if it still doesn\'t work using the <a href="?c=Contact">contact form</a>.';
				debug($e->getQuery());
				$content[] = $this->showForm();
			} catch (PDOException $e) {
				debug($e->getMessage());
				debug($this->db->lastQuery);
			}
		} else {
			$content[] = $this->showFormWithValidation();
		}
		return $content;
	}

	public function insertRecord(array $userData)
	{
		$this->model->insert($userData);
		return $this->afterInsert($userData);
	}

	public function afterInsert(array $userData)
	{
		return '<div class="success">' . __('Inserted') . '</div>';
	}

	public function updateRecord(array $userData)
	{
		//debug($this->model->data, $userData);
		$this->model->update($userData);    // update() returns nothing
		//debug($this->model->data, $this->model->lastQuery);
		return $this->afterUpdate($userData);
	}

	public function afterUpdate(array $userData)
	{
		return '<div class="success">' . __('Updated') . '</div>';
	}

	public function showFormWithValidation()
	{
		$v = new HTMLFormValidate($this->form);
		if ($v->validate()) {
			$content[] = $this->showForm();
		} else {
			$content[] = '<div class="error ui-state-error">Validation failed. Check your form below:</div>';
			$content[] = $v->getErrorList();
			$content[] = $this->showForm();
		}
		return $content;
	}

	public function showDefault()
	{
		$content[] = $this->showTable();
		$content[] = $this->showButtons();
		$content[] = '<div id="' . $this->formPrefix . '"></div>'; // container for all AJAX add/edit forms
		return $content;
	}

	/**
	 * Collection is way better to display raw data
	 *
	 * @return string
	 * @throws Exception
	 * @deprecated
	 */
	public function showTable()
	{
		$data = [$this->model->data];
		$data = $this->processData($data);

		if ($data) {
			debug($this->model->data, $data);
			$s = new slTable($data, 'class="nospacing spaceBelow"');
			$s->thes($this->thes);
			$content = $s->getContent();
		} else {
			$content = '<div class="message">No data found.</div>';
		}
		return $content;
	}

	public function processData(array $data)
	{
		foreach ($data as &$row) {
			$row['edit'] = $this->getEditIcon($row['id']);
		}
		return $data;
	}

	public function getEditIcon($id)
	{
		//makeAjaxLink
		$aTag = $this->makeLink($this->editIcon, [
			'c' => get_class($this),
			'pageType' => get_class($this),
			'ajax' => true,
			'action' => 'showEdit',
			$this->table . '.id' => $id,
		], $this->formPrefix);
		$href = $aTag->attr['href'];
		/** @var URL $href */
		$aTag->attr['href'] = $href->buildQuery();
		return $aTag;
	}

	protected function showButtons()
	{
		$content = $this->makeAjaxLink('<button>' . $this->addButton . '</button>', [
			'c' => get_class($this),
			'ajax' => true,
			'action' => 'showForm',
		], $this->formPrefix, '', ['class' => "button"]);
		return $content;
	}

	public function getDescFromThes()
	{
		$special = ['id', 'match', 'mtime', 'muser'];
		$desc = [];
		foreach ($this->model->thes as $key => $k) {
			$k = is_array($k) ? $k : ['name' => $k];
			if (!in_array($key, $special) && $k['showSingle'] !== false) {
				$desc[$key] = [
						'label' => $k['name'],
						'type' => $k['type'],
						'value' => $this->model->data[$key],
					] + $k;
				if ($k['type'] == 'combo') {
					$desc[$key]['options'] = $this->db->getTableOptions(
						$this->model->table,
						$key,
						[],
						'ORDER BY ' . $this->db->quoteKey($key),
						$key
					);
				}
			}
		}
		return $desc;
	}

}
