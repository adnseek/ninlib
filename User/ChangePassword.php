<?php

class ChangePassword extends HTMLFormProcessor
{

	protected $minLength = 8;

	protected $submitButton = 'Change';

	public $title = 'Change Password';

	public function getDesc()
	{
		$desc = [];
		$desc['action'] = [
			'type' => 'hidden',
			'value' => 'changePassword',
		];
		$desc['current'] = [
			'label' => 'Current password',
			'type' => 'password',
		];
		$desc['password']['label'] = __('Password');
		$desc['password']['append'] = __('Min: %s chars.', $this->minLength);
		$desc['password']['type'] = 'password';
		$desc['password']['minlen'] = $this->minLength;
		$desc['repeat']['label'] = __('Repeat again');
		$desc['repeat']['type'] = 'password';
		return $desc;
	}

	public function onSuccess(array $data)
	{
		$content = '';
		if (strlen($data['password']) >= $this->minLength) {
			if ($data['password'] == $data['repeat']) {
				$ok = $this->user->updatePassword($data['current'], $data['password']);
				if (!$ok['error']) {
					$content .= $this->success(__('Password changed.'));
				} else {
					$content .= $this->error($ok['message']);
				}
			} else {
				throw new Exception(__('Passwords mismatch. Please try again.'));
			}
		} else {
			throw new Exception(__('Minimum password length is %s characters.', $this->minLength));
		}
		return $content;
	}

}
