<?php

class SearchPlaceholder extends AppControllerBE
{

	public $ajaxLinks = [
		'?c=SearchPlaceholder&action=sleep&time=1',
		'?c=SearchPlaceholder&action=sleep&time=2',
		'?c=SearchPlaceholder&action=sleep&time=3',
		'?c=SearchPlaceholder&action=sleep&time=4',
		'?c=SearchPlaceholder&action=sleep&time=5',
		'?c=SearchPlaceholder&action=sleep&time=6',
		'?c=SearchPlaceholder&action=sleep&time=7',
		'?c=SearchPlaceholder&action=sleep&time=8',
		'?c=SearchPlaceholder&action=sleep&time=9',
		'?c=SearchPlaceholder&action=sleep&time=10',
	];

	public function render()
	{
		$content = $this->performAction($this->detectAction());
		if (!$content) {
			$content .= $this->renderProgressBar();
		}
		return $content;
	}

	public function renderProgressBar()
	{
		$pb = new ProgressBar(1);
		$pb->destruct100 = false;
		$content = $pb->getContent();

		foreach ($this->ajaxLinks as &$link) {
			$link .= '&pbid=' . $pb->pbid;
		}

		$content .= '<script> var ajaxLinks = ' . json_encode($this->ajaxLinks) . '; </script>';
		$content .= '<div id="SearchPlaceholder"></div>';
		$this->index->addJQuery();
		//$this->index->footer[] = '<script> jQuery.noConflict(); </script>';
		$nadlibFromDocRoot = AutoLoad::getInstance()->nadlibFromDocRoot;
		$this->index->addJS($nadlibFromDocRoot . 'js/SearchPlaceholder.js');
		return $content;
	}

	public function sleepAction()
	{
		sleep(1);
		$time = $this->request->getInt('time');
		$content = 'asd ' . $time;

		$pb = new ProgressBar();
		$pb->destruct100 = false;
		$pbid = $this->request->getTrim('pbid');
		$pb->setID($pbid);

		$pb->setProgressBarProgress(100 * $time / sizeof($this->ajaxLinks));
		if ($time == sizeof($this->ajaxLinks)) {
			$pb->hide();
		}

		return $content;
	}

}
