<?php

use spidgorny\nadlib\HTTP\URL;

trait JsonController
{

	public static $publicAPI = false;
	public $user;

	public function afterConstruct()
	{
		$this->request->set('ajax', true);
		$this->user = new APIUser();  // prevent API to hijack user session
		$this->config->setUser($this->user);
	}

	/**
	 * @param array $registeredApps
	 * @throws LoginException
	 */
	public function validateAuthorization(array $registeredApps)
	{
		list($actionClass) = $this->getActionAndArguments();
		$obj = new $actionClass();
		if ($obj::$publicAPI) {
			return;
		}

		$headers = $this->request->getHeaders();
//		llog('apache_headers in JsonController for', get_class($this), $headers);

		$authorization = $this->request->getHeader('Authorization');
//		llog($authorization);
		//debug($headers, $authorization);
		invariant($authorization, 'No Authorization Header', 401);
		if (!in_array($authorization, $registeredApps)) {
			throw new LoginException('Authorization failed.', 401);
		}
	}

	public function getActionAndArguments()
	{
		// debug($_SERVER);
		$requestURI = ifsetor($_SERVER['REQUEST_URI']);
		$url = new URL($requestURI);
		$levels = $url->getPath()->getLevels();

		if ($levels[0] === 'stage') {
			$levels = array_slice($levels, 1);
		}
		if ($levels[0] === 'API') {
			$levels = array_slice($levels, 1);
		}
//		llog('API Levels', $levels);

		// next after /API/
		//llog(get_class($this));
		$last = null;
		$arguments = [];
		$thisParents = new ReflectionClass($this);
		$thisParents = [
			get_class($this),
			$thisParents->getParentClass()->getName(),
			'SoftwareImage',
			'SoftwareImg',
			AddVersion::class,
			AddJob::class,
			ArchiveComplete::class,
		];
//		llog('$thisParents', $thisParents);
		foreach (array_reverse($levels) as $i => $el) {
			$isThisController = in_array($el, $thisParents);
//			llog(get_class($this), $el, 'isThisController:', $isThisController);
			if ($isThisController) {
				$last = $el;
				$arguments = array_slice($levels, count($levels) - $i);    // rest are args
//				llog('arguments', $arguments);
				break;
			}
		}
		if (!$last) {
			$last = last($levels);
		}
		$request = trim($last, '/\\ ');
		$request = explode('.', $request)[0];
//		llog(['request' => $request, 'arguments' => $arguments]);
		return [$request, $arguments];
	}

	public function __invoke()
	{
		list($request, $arguments) = $this->getActionAndArguments();
		return call_user_func_array([$this, $request], $arguments);
	}

	public function jsonError(Exception $e, $httpCode = 500, array $extraData = [])
	{
		$message = '[' . get_class($e) . ']' . PHP_EOL . $e->getMessage() . PHP_EOL . $e->getFile() . '#' . $e->getLine();
		llog(get_class($this), $message);
		http_response_code($httpCode);
		return $this->json([
				'status' => 'error',
				'error_type' => get_class($e),
				'message' => $e->getMessage(),
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'stack_trace' => DEVELOPMENT ? trimExplode("\n", $e->getTraceAsString()) : null,
				'request' => $this->request->getAll(),
				'headers' => getallheaders(),
				'timestamp' => date('Y-m-d H:i:s'),
				'back_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
				'duration' => microtime(true) - $_REQUEST['REQUEST_TIME_FLOAT'],
			] + $extraData);
	}

	public function json($key)
	{
		header('Content-Type: application/json');
		$key['duration'] = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
		$response = json_encode($key, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES  /*JSON_UNESCAPED_LINE_TERMINATORS*/);
//		error_log($response);
		return $response;
	}

}
