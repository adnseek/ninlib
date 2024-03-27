<?php

class ListFilesIn extends ArrayObject
{

	public function __construct($folder)
	{
		//parent::__construct();
		$menu = [];
		if (file_exists($folder)) {
			$iterator = new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS);
			//$iterator = new RecursiveIteratorIterator($iterator);
			foreach ($iterator as $file) {
				/** @var $file SplFileInfo */
				$filename = $file->getFilename();
				if ($filename[0] !== '.') {
					$pathname = $file->getPathname();
					//$key = first(trimExplode('.', $filename, 2));	// why?
					$key = $filename;
					//debug($filename, $pathname, $key);
					if ($file->isDir()) {
						$children = new self($folder . $filename);
						$menu[$key] = new Recursive($key, $children->getArrayCopy());
					} else {
						$menu[$key] = File::fromSpl($file);
					}
				}
			}
		}
		//debug($folder, $menu);
		parent::__construct($menu);
	}

	/**
	 * @param mixed $index
	 * @return SplFileInfo[]
	 */
	public function offsetGet(mixed $index): mixed
	{
		return parent::offsetGet($index);
	}

}
