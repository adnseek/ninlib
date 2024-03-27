<?php

class Issues extends AppControllerBE
{

	public $bitbucketID;

	public function render()
	{
		//debug($this->config->config);
		//debug($this->bitbucketID);
		//$this->bitbucketID = $this->config->config['Issues']['bitbucketID'];

		$content[] = 'ID: ' . $this->bitbucketID . BR;
		if ($this->bitbucketID) {
			$url = 'https://bitbucket.org/api/1.0/repositories/' . $this->bitbucketID . '/issues';
			$content[] = 'URL: ' . $url . BR;
			$ug = new URLGet($url);
			//$ug->setProxy('', '', '');
			$ug->curlParams[CURLOPT_SSL_VERIFYPEER] = true;
			$ug->curlParams[CURLOPT_SSL_VERIFYHOST] = 2;
			$ug->curlParams[CURLOPT_CAINFO] = cap(__DIR__) . 'bitbucket.org.crt';
			//$ug->curlParams[CURL_CA_BUNDLE] = 'C:\Users\DEPIDSVY\NetBeansProjects\vdrive\.sys\php\cacert.pem';
			//$ug->fetch();
			//debug(getenv('http_proxy'));
			$ug->context = [
				'http' => [
					'proxy' => str_replace('http://', 'tcp://', getenv('http_proxy')),
					'request_fulluri' => true,
				],
				'https' => [
					'proxy' => str_replace('http://', 'tcp://',
						getenv('https_proxy') ?: getenv('http_proxy')),
				],
				'_ssl' => [
					'verify_peer' => false,
					'allow_self_signed' => true,
				],
			];
			$ug->fetchFOpen();
			$json = $ug->getContent();
			$content[] = $json;
		}
		return $content;
	}

}
