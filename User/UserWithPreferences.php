<?php

interface UserWithPreferences
{

	public function getPref($key);

	public function setPref($key, $val);

}
