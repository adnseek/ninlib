<?php

class SessionView extends AppControllerBE
{

	public function __construct()
	{
		parent::__construct();
		ksort($_SESSION);
		//debug(AutoLoad::getInstance()->debug());
		$this->index->addJS(AutoLoad::getInstance()->nadlibFromDocRoot . 'js/keepScrollPosition.js');
	}

	public function render()
	{
		$this->performAction($this->detectAction());
		$content = '';
		foreach ($_SESSION as $key => $val) {
			$content .= '<h4>
				<a name="' . $key . '">
					' . $key . '
				</a>
				<a href="' . $this->makeURL([
					'c' => 'Session',
					'action' => 'del',
					'del' => $key,
				]) . '">&times;</a>
			</h4>' .
				getDebug($val);
		}
		return $content;
	}

	public function delAction()
	{
		$del = $this->request->getTrim('del');
		unset($_SESSION[$del]);
		$this->index->message('Deleted ' . $del);
		$this->index->content->saveMessages();
		$this->request->redirect('?c=' . $this->request->getRefererController());
	}

	public function sidebar()
	{
		$keys = array_keys($_SESSION);
		foreach ($keys as &$key) {
			$key = '<a href="#' . $key . '">' . $key . '</a>';
		}
		$content[] = implode('<br />', $keys);

		$content[] = BR;

		$ini = ini_get_all('session');
		$ini = ArrayPlus::create($ini)->column('local_value')->getData();
		//$content[] = getDebug($ini);
		//$content[] = slTable::showAssoc($ini);
		$ul = new UL($ini);
		$ul->before = '<dl>';
		$ul->after = '</dl>';
		$ul->wrap = '<dt>###CLASS###</dt><dd>|</dd>';
		$content[] = $ul;
		return $content;
	}

}
