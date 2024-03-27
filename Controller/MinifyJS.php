<?php

class MinifyJS
{

	protected $footer = [];

	public function __construct(array $footer)
	{
		$this->footer = $footer;
	}

	public function implodeJS()
	{
		// composer require mrclay/minify
		$index_php = __DIR__ . '/../../../mrclay/minify/index.php';
//		debug($index_php, file_exists($index_php));
		if (!file_exists($index_php)) {
			return null;
		}
		$include = []; // some files can't be found
		$files = array_keys($this->footer);

		$docRoot = realpath($_SERVER['DOCUMENT_ROOT']);
		$docRoot = str_replace('\\', '/', $docRoot);

		// make absolute paths and check file exists
		foreach ($files as $f => &$file) {
			if (file_exists($file)) {
				if (!Path::isItAbsolute($file)) {
					$file = $docRoot . $file;
				}
				$file = realpath($file);
				$file = str_replace('\\', '/', $file);    // fix windows
//					debug($file, file_exists($file), Path::isItAbsolute($file));
			} else {
				unset($files[$f]);
				$include[$file] = $this->footer[$file];
			}
		}

		// remove common base folder
		// "slawa/mrbs/"
//			Request::printDocumentRootDebug();
//			debug($_SERVER);
		foreach ($files as $f => &$file) {
			$file2 = substr(
				$file,
				strpos($file, $docRoot) + strlen($docRoot)
			);
//				debug($docRoot, $file, $file2);
			$file = $file2;
		}

		$path = 'vendor/mrclay/minify/';
		$path .= '?' . http_build_query([
				//'b' => $docRoot,
				'f' => implode(",", $files),
			]);
		$content = '<script src="' . $path . '"></script>' . PHP_EOL;
		$content .= implode("\n", $include);
//			debug($content);
		return $content;
	}
}
