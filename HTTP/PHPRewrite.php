<?php

/**
 * Class PHPRewrite - a replacement for RewriteRule in .htaccess, just add:
 * ErrorDocument 404 /index.php?c=PHPRewrite
 * @acl *
 */
class PHPRewrite extends AppControllerBE
{

	/**
	 * @acl *
	 */
	public function render()
	{
		//debug($_SERVER);
		//$originalURL = $_SERVER['REDIRECT_URL'];	// Apache
		//$prefix = $this->request->getPathAfterDocRoot();
		//debug($originalURL, $prefix);
		$controller = $this->request->getPathAfterDocRoot() . '';
		if (class_exists($controller)) {
			http_response_code(200);
			$object = new $controller();
			$content[] = $object->render();
			$this->title = $object->title;
		} else {
			throw new Exception404($controller);
		}
		return $content;
	}

}
