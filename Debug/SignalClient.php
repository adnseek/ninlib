<?php

use GuzzleHttp\Client;

/**
 * Class SignalClient - send vitality information to the signal server
 */
class SignalClient
{

	/**
	 * @var string URL
	 */
	public $endpoint;

	/**
	 * @var Client
	 */
	public $guzzle;

	public function __construct($endpoint, GuzzleHttp\Client $guzzle)
	{
		$this->endpoint = $endpoint;
		$this->guzzle = $guzzle;
	}

	public function success($message)
	{
		$this->guzzle->post($this->endpoint . '/success', [
			'message' => $message,
		]);
	}

	public function exception(Exception $e)
	{
		$this->guzzle->post($this->endpoint . '/exception', [
			'code' => $e->getCode(),
			'file' => $e->getFile(),
			'line' => $e->getLine(),
			'message' => $e->getMessage(),
			'trace' => $e->getTraceAsString(),
		]);
	}

	public function error($message)
	{
		$this->guzzle->post($this->endpoint . '/error', [
			'message' => $message,
		]);
	}

}
