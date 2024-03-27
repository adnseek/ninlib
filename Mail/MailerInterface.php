<?php

interface MailerInterface
{

	public function __construct($to, $subject, $body);

	public function send();

}
