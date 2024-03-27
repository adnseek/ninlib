<?php

use SendGrid\Response;

/**
 * Class Mailer - simple mail sending class which supports either plain text or HTML
 * mails. No attachments. Use SwiftMailer for anything more complicated. Takes care
 * of the UTF-8 in subjects.
 */
class Mailer implements MailerInterface
{

	/**
	 * @var string
	 */
	public $to = [];

	public $cc;

	public $bcc;

	/**
	 * @var string
	 */
	public $subject;

	/**
	 * @var string
	 */
	public $bodytext;

	public $headers = [];

	public $params = [];

	/**
	 * @return \SendGrid\Response
	 */
	public function sendGrid()
	{
		$config = Config::getInstance();
		$mail = $this->getSendGridMail();

		$sg = $config->getSendGrid();

		/** @var $response \SendGrid\Response */
		$response = $sg->client->mail()->send()->post($mail);
		return $response;
	}

	public function getSendGridMail()
	{
		$config = Config::getInstance();
		$from = new SendGrid\Email(null, $config->mailFrom);
		$to = new SendGrid\Email(null, $this->to);
		$content = new SendGrid\Content('text/plain', $this->getPlainText());
		$mail = new SendGrid\Mail($from, $this->subject, $to, $content);
		return $mail;
	}

	/**
	 * @return bool
	 * @throws MailerException
	 */
	public function send()
	{
		$emails = $this->to;
		$validEmails = 0;
		foreach ($emails as $e) {
			$validEmails += HTMLFormValidate::validEmail($e);
		}
		if ($validEmails === sizeof($emails)) {
			$res = mail(
				implode(', ', $this->to),
				$this->getSubject(),
				$this->getBodyText(),
				implode("\n", $this->headers) . "\n",
				implode(' ', $this->params)
			);
			if (!$res) {
				throw new MailerException('Email sending to [' . implode(', ', $this->to) . '] failed');
			}
		} else {
			throw new MailerException('Invalid email address: ' . implode(', ', $this->to));
		}
		return $res;
	}

	public function __construct($to, $subject, $body)
	{
		$this->to = $to;
		$this->subject = $subject;
		$this->bodytext = $body;
	}

	public function getPlainText()
	{
		return strip_tags($this->bodytext);
	}

	public function getSubject()
	{
		return $this->subject;
	}

	public function getBodyText()
	{
		return $this->bodytext;
	}
}
