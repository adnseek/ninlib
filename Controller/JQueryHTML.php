<?php

class JQueryHTML extends HTML
{

	public function error($content)
	{
		return '<div class="ui-state-error">' . $this->s($content) . '</div>';
	}

}
