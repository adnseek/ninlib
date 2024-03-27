<?php

class SwiftMailer implements MailerInterface
{

	/**
	 * @var array
	 */
	public $to;
	public $subject;
	public $body;
	public $from;
	public $fromName;

	/** @var Swift_Transport */
	public $transport;

	public function __construct($to, $subject, $body)
	{
		if (!is_array($to)) {
			$to = trimExplode(',', $to);
		}
		$this->to = $to;
		$this->subject = $subject;
		$this->body = $body;
		$this->transport = new Swift_SendmailTransport();
	}

	public function send($cc = null, $bcc = null, array $attachments = [], array $additionalSenders = [])
	{
		return $this->sendSwiftMailerEmail($cc, $bcc, $attachments, $additionalSenders);
	}

	/**
	 * Method to send emails via SwiftMailer.
	 * Throws an Exception if SwiftMailer is not installed.
	 *
	 * Uses sendmail to deliver messages.
	 *
	 * @param mixed $cc
	 * @param mixed $bcc
	 * @param array $attachments
	 * @param array $additionalSenders This will be added to
	 * @return int|array Either number of recipients who were accepted for delivery OR an array of failed recipients
	 * @throws Exception
	 */
	public function sendSwiftMailerEmail($cc = null, $bcc = null, array $attachments = [], array $additionalSenders = [])
	{
		$message = $this->getSwiftMessage($cc, $bcc, $attachments, $additionalSenders);

		$mailer = new Swift_Mailer($this->transport);
		$failedRecipients = [];
		$sent = $mailer->send($message, $failedRecipients);

		return count($failedRecipients) ? $failedRecipients : $sent;
	}

	/**
	 * @param array $cc
	 * @param array $bcc
	 * @param array $attachments
	 * @param array $additionalSenders - assoc array
	 * @return Swift_Message
	 * @throws Exception
	 */
	public function getSwiftMessage($cc = null, $bcc = null, $attachments = [], array $additionalSenders = [])
	{
		$messageHTML = $this->body;
		$messageText = strip_tags($this->body);

		/** @var Swift_Message $message */
		$message = new Swift_Message();
		$message->setSubject($this->subject)
			->setBody($messageHTML, 'text/html')
			->addPart($messageText, 'text/plain');
		$message->setCharset('utf-8');

		$message->setFrom($this->from, $this->fromName);

		foreach ($additionalSenders as $address => $_) {
			$message->addFrom($address);
		}

		$to = $this->to;
		foreach ($to as $address) {
			$message->addTo(trim($address));
		}

		if (!empty($cc)) {
			foreach ($cc as $address) {
				$message->addCc($address);
			}
		}

		if (!empty($bcc)) {
			foreach ($bcc as $address) {
				$message->addBcc($address);
			}
		}

		if (!empty($attachments)) {
			foreach ($attachments as $attachment) {
				if (is_string($attachment)) {
					$smAttachment = Swift_Attachment::fromPath($attachment);
					$shortFile = $this->getShortFilename($attachment);
					$smAttachment->setFilename($shortFile);
					$message->attach($smAttachment);
				} else {
					$message->attach($attachment);
				}
			}
		}

		if (!empty($additionalSenders)) {
			foreach ($additionalSenders as $address => $name) {
				if (!empty($address)) {
					$message->addFrom($address, $name);
				}
			}
		}

		return $message;
	}

	/**
	 * http://stackoverflow.com/questions/8781911/remove-non-ascii-characters-from-string-in-php
	 * @param string $attachment
	 * @return string
	 */
	public function getShortFilename($attachment)
	{
		$pathInfo = pathinfo($attachment);
		$ext = $pathInfo['extension'];

		$filename = $pathInfo['filename'];
		$filename = filter_var($filename, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
		$filename = preg_replace('/([\s])\1+/', ' ', $filename);
		$filename = str_replace(' ', '_', $filename);

		$extLen = 1 + strlen($ext);
		$shortFile = substr($filename, 0, 63 - $extLen)
			. '.' . $ext;
		return $shortFile;
	}

}
