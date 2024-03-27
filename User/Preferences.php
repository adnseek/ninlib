<?php

class Preferences
{

	/**
	 * @var LoginUser|NoUser
	 */
	protected $user;

	public $prefs = [];    // for debug

	/**
	 * Preferences constructor.
	 * @param $user UserModelInterface
	 */
	public function __construct($user)
	{
		$this->user = $user;
		$dbPrefs = ifsetor($this->user->data['prefs']);
		if ($dbPrefs) {
			$this->prefs = unserialize($dbPrefs);
		}
	}

	public function set($key, $val)
	{
		$this->prefs[$key] = $val;
		//debug(__METHOD__, typ($this), $key, $val);
	}

	public function get($key, $default = null)
	{
		//debug(__METHOD__, $key);
		return ifsetor($this->prefs[$key], $default);
	}

	public function un_set($key)
	{
		unset($this->prefs[$key]);
	}

	public function getSetPref($key, $prio1 = null, $prio3 = null)
	{
		$prio2 = $this->get($key);
		if ($prio1 != null) {
			$val = $prio1;
		} elseif ($prio2 != null) {
			$val = $prio2;
		} else {
			$val = $prio3;
		}
		/*		debug(array(
					$prio1,
					$prio2,
					$prio3,
					$val,
				));
		*/
		$this->set($key, $val);
		return $val;
	}

	public function serialize()
	{
		return serialize($this->prefs);
	}

	public function getData()
	{
		return $this->prefs;
	}

}
