<?php

class LazyTreeOptions {

	/**
	 * Not used? Kept just in case.
	 *
	 * @var string
	 */
	public $table;

	/**
	 * The class of LazyTree Nodes
	 *
	 * @var string
	 */
	public $class;

	/**
	 * The original root ID for the whole tree.
	 * TODO: array of root IDs should be possible
	 *
	 * @var integer|array
	 */
	public $rootID;

	/**
	 * The selected node highlighted.
	 *
	 * @var integer
	 */
	public $selectedNode;

	/**
	 * Open nodes not to retrieve them every time.
	 *
	 * @var array(id => boolean)
	 */
	public $openTreeNodes;

	/**
	 * Name of the module to search in preferences['openTreeNodes']
	 * Used to *update* the openTreeNodes.
	 *
	 * @var string
	 */
	public $module;

	/**
	 * The name by which it can be accessed inside the session
	 *
	 * @var string
	 */
	public $sessionID;

	/**
	 * For javascript IDs of the hidden field and div with the tree (for refresh)
	 *
	 * @var string $receptorID
	 */
	public $receptorID;

	public $containerID;

	/**
	 * Whereas rootID is the same for the complete tree selection div,
	 * we are loading different portion separately
	 * and need to make sure the first loaded is automatically opened.
	 *
	 * @var int|int[]
	 */
	public $requestRoot;

	public $where = array();

	function __construct($table = NULL, $class = NULL) {
		$this->table = $table;
		$this->class = $class;
	}

	/**
	 * @return LazyTreeBase
	 */
	function getTreeInstance() {
		/** @var OODBase $class */
		$class = $this->class;
//		if (ifsetor($class::$instances[$class][$this->requestRoot])) {
//			return $class::$instances[$class][$this->requestRoot];
//		}
		if (is_array($this->requestRoot)) {
			$start = NULL;
		} else {
			$start = $this->requestRoot;
		}
		/** @var LazyTreeBase $obj */
		$obj = new $this->class($start, $this);
		$obj->id = $this->requestRoot;
		$obj->loadOpenTreeNodes();
		$obj->openPathToSelected();
		return $obj;
	}

}
