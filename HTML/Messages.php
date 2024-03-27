<?php

namespace nadlib\HTML;

use MergedContent;

class Messages extends MergedContent
{

	public function message($text)
	{
		$text = \MergedContent::mergeStringArrayRecursive($text);
		$msg = '<div class="message alert alert-info ui-state-message alert alert-notice padding">' . $text . '</div>';
		if (is_array($this->content)) {
			$this->content[] = $msg;
		} else {
			$this->content .= $msg;
		}
		return $msg;
	}

	public function error($text)
	{
		$text = \MergedContent::mergeStringArrayRecursive($text);
		$msg = '<div class="error error_top ui-state-error alert alert-error alert-danger padding">' . $text . '</div>';
		if (is_array($this->content)) {
			$this->content[] = $msg;
		} else {
			$this->content .= $msg;
		}
//		debug($this->content);
		return $msg;
	}

	public function success($text)
	{
		$text = \MergedContent::mergeStringArrayRecursive($text);
		$msg = '<div class="alert alert-success padding">' . $text . '</div>';
		if (is_array($this->content)) {
			$this->content[] = $msg;
		} else {
			$this->content .= $msg;
		}
		return $msg;
	}

	public function info($text)
	{
		$text = \MergedContent::mergeStringArrayRecursive($text);
		$msg = '<div class="alert alert-info padding">' . $text . '</div>';
		if (is_array($this->content)) {
			$this->content[] = $msg;
		} else {
			$this->content .= $msg;
		}
		return $msg;
	}

	public function saveMessages()
	{
		$_SESSION[__CLASS__]['messages'] = $this->content;
	}

	public function restoreMessages()
	{
//		debug('restoring');
		if (isset($_SESSION[__CLASS__])) {
			$this->content = $_SESSION[__CLASS__]['messages'];
			$_SESSION[__CLASS__]['messages'] = [];
		}
	}

}
