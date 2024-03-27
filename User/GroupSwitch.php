<?php

use spidgorny\nadlib\HTTP\URL;

class GroupSwitch extends Controller
{

	/**
	 * Debugging is only enabled for these people.
	 * This is not a standard Nadlib functionality
	 * @see Index
	 * @var array
	 */
	public $allowedUsers = [];

	protected $groups = [];

	public function render()
	{
		//debug($this->user->data);
		$content = '';
		if ($this->canSwitchGroup()) {
			$this->performAction($this->detectAction());
			$this->groups = $this->fetchGroups();
			$content = $this->renderGroups();
		}
		return $content;
	}

	public function canSwitchGroup()
	{
		return in_array($this->user->getLogin(), $this->allowedUsers);
	}

	public function fetchGroups()
	{
		return $this->groups;
	}

	public function renderGroups()
	{
		$items = [];
		foreach ($this->groups as $groupID => $groupName) {
			$el = $this->makeLink($groupName, [
					'action' => 'setGroup',
					'groupID' => $groupID,
				], get_class($this)) . ' ';
			if ($this->isCurrentGroup($groupID)) {
				$el = '<b>' . $el . '</b>';
			}
			$items[] = $el;
		}
		$content = implode(' | ', $items);
		return $content;
	}

	public function isCurrentGroup($groupID)
	{
		return $this->user->rights->groupID == $groupID;
	}

	public function setGroupAction()
	{
		$this->user->pretendOtherDepartment($this->request->getInt('groupID'));
		$referer = new URL($_SERVER['HTTP_REFERER']);
		//$referer->setParams();	// uncommented to let ORS redirect to the same RequestInfo?id=123
		$this->request->redirect($referer);
	}

}
