<?php

/**
 * Class TranslateLL
 * TODO: Combine with Localize
 * No, don't combine and this is used to localize a SINGLE message.
 * Admins get here by using clickTranslate.js
 */
class TranslateLL extends HTMLFormProcessor
{

	protected $submitButton = 'Update';

	/**
	 * @var CookieUser|User|LoginUser
	 */
	public $user;

	public function __construct()
	{
		parent::__construct();
		$this->user = Config::getInstance()->getUser();
		if (!$this->user || !$this->user->id || !$this->user->isAdmin()) {
			throw new AccessDeniedException();
		}
	}

	public function getDesc()
	{
		$ll = $this->config->getLL();
		$code = $this->request->getTrim('code');
		$desc = [
			'lang' => [
				'label' => __('Lang'),
				'more' => [
					'readonly' => "1",
				],
				'value' => $ll->lang,
			],
			'code' => [
				'label' => __('Code'),
				'more' => [
					'readonly' => "1",
				],
				'value' => $code,
			],
			'text' => [
				'label' => __('Trans'),
				'type' => 'textarea',
				'value' => $ll->ll[$code],
				'more' => [
					'rows' => 20,
				]
			],
		];
		return $desc;
	}

	public function render()
	{
		$content = parent::render();
		$content .= '<iframe
			src="http://dict.leo.org/ende?search=' .
			urlencode($this->request->getTrim('code')) . '"
			width="100%"
			height="500"></iframe>';
		return $content;
	}

	public function onSuccess(array $data)
	{
		$ll = $this->config->getLL();
		$ll->updateMessage($data);
		$content = '<div class="message">Updated.</div>';
		$content .= '<script>
			window.opener.location.href = window.opener.location.href;
			window.close();
		</script>';
		//debug($ll->ll, json_encode($ll->ll));
		return $content;
	}

}
