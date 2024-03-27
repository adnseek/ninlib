<?php

class FakeMailer implements MailerInterface
{

	public $emails;

	public $subject;

	public $body;

	public function __construct($emails, $subject, $body)
	{
		$this->emails = $emails;
		$this->subject = $subject;
		$this->body = $body;
	}

	public function send()
	{
		$emails = is_array($this->emails)
			? implode('; ', $this->emails)
			: $this->emails;
//		echo 'Sending mail "' . $this->subject . '" to [' . $emails . ']', BR;
	}

	public function sendSwiftMailerEmail($cc = null, $bcc = null, array $attachments = [], array $additionalSenders = [])
	{
		//pre_print_r(__METHOD__, get_object_vars($this), func_get_args());
		$message = $this->getSwiftMessage($cc, $bcc, $attachments, $additionalSenders);

		$transport = new Swift_NullTransport();  // <== null
		$mailer = new Swift_Mailer($transport);
		$failedRecipients = [];
		$sent = $mailer->send($message, $failedRecipients);

		return count($failedRecipients) ? $failedRecipients : $sent;
	}

	public function getSwiftMessage($cc, $bcc, array $attachments, $additionalSenders)
	{
		$orsMailer = new SwiftMailer($this->emails, $this->subject, $this->body);
		return $orsMailer->getSwiftMessage($cc, $bcc, $attachments, $additionalSenders);
	}

}
