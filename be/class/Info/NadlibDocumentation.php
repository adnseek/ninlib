<?php

use spidgorny\nadlib\HTTP\URL;

class NadlibDocumentation extends AppControllerBE
{

	protected $folder;

	protected $class;

	protected $method;

	public function render()
	{
		$content = '';
		$this->folder = $this->request->getTrim('folder');
		if ($this->folder) {
			$files = $this->getFiles($this->folder);
			$this->class = $this->request->getTrim('class');
			$content .= $this->listFiles($files);
			if ($this->class) {
				$content .= $this->renderClass();
			}
		}
		return $content;
	}

	public function getFiles($folder)
	{
		$files = [];
		$it = new DirectoryIterator($folder);
		foreach ($it as $file) {
			/** @var $file SplFileInfo */
			if ($file->isFile()) {
				$filename = $file->getFilename();
				if (str_startsWith($filename, 'class.')) {
					$files[$file->getPathname()] = $file->getFilename();
				}
			}
		}
		return $files;
	}

	public function listFiles(array $files)
	{
		foreach ($files as $path => &$file) {
			$class = str_replace('class.', '', $file);
			$class = str_replace('.php', '', $class);
			$file = new HTMLTag('a', [
				'href' => new URL('', [
					'c' => __CLASS__,
					'folder' => dirname($path),
					'class' => $class,
				]),
				'class' => $this->class == $class ? 'bold' : '',
			], $file);
		}
		$content = '<ul style="-moz-column-count: 2"><li>' . implode('</li><li>', $files) . '</li></ul>';
		return $content;
	}

	public function renderClass()
	{
		$content = '';
		$content .= '<hr><h4 style="display: inline-block;">' . $this->class . '</h4>';
		$rc = new ReflectionClass($this->class);
		$content .= ' <small class="muted">
			<a href="file://' . $rc->getFileName() . '">' . $rc->getFileName() . '</a>
		</small>';
		$content .= $this->getParentClassLinks($rc);
		$content .= '<pre>' . htmlspecialchars($rc->getDocComment()) . '</pre>';
		$methods = $rc->getMethods();
		$this->method = $this->request->getTrim('method');
		$content .= $this->listMethods($this->folder, $this->class, $methods);
		if ($this->method) {
			$content .= $this->renderMethod($rc);
		}
		return $content;
	}

	public function getParentClassLinks(ReflectionClass $rc, $level = 0)
	{
		$content = '';
		if ($rc->getParentClass()) {
			$content .= ' <i class="icon-arrow-right"></i> ' .
				new HTMLTag('a', [
					'href' => new URL('', [
						'c' => __CLASS__,
						'folder' => $this->folder,
						'class' => $rc->getParentClass()->getName(),
					]),
				], $rc->getParentClass()->getName()
				);
			$content .= $this->getParentClassLinks($rc->getParentClass(), $level + 1);
		}
		if (!$level) {
			$content = '<h5>' . $content . '</h5>';
		}
		return $content;
	}

	public function listMethods($folder, $class, array $methods)
	{
		foreach ($methods as &$method) {
			$method = $method->getName();
			$method = new HTMLTag('a', [
				'href' => new URL('', [
					'c' => __CLASS__,
					'folder' => $folder,
					'class' => $class,
					'method' => $method,
				]),
				'class' => $this->method == $method ? 'bold' : '',
			], $method);
		}
		$content = '<ul style="-moz-column-count: 3"><li>' . implode('</li><li>', $methods) . '</li></ul>';
		return $content;
	}

	public function renderMethod(ReflectionClass $rc)
	{
		$content = '';
		$rf = $rc->getMethod($this->method);
		$content .= '<pre>' . htmlspecialchars("\t" . $rf->getDocComment()) . '</pre>';
		$content .= implode(' ', Reflection::getModifierNames($rf->getModifiers())) . ' <b>' . $rf->getName() . '</b>';
		$params = $rf->getParameters();
		if ($params) {
			foreach ($params as &$param) {
				$line = $param->getPosition() . ': ';
				//$line .= $param->getName();
				if ($param->isDefaultValueAvailable()) {
					//$line .= ' = '.var_export($param->getDefaultValue(), true);
				}
				$line .= substr(substr($param->__toString(), 14), 0, -1);
				$param = $line;
			}
			$content .= '<ul><li>' . implode('</li><li>', $params) . '</li></ul>';
		}
		return $content;
	}

	public function sidebar()
	{
		$content = '';
		$folders = $this->getFolders();
		foreach ($folders as $path => &$file) {
			$file = new HTMLTag('a', [
				'href' => new URL('', [
					'c' => __CLASS__,
					'folder' => $path,
				]),
				'class' => $this->folder == $path ? 'bold' : '',
			], $file);
		}
		$content .= '<ul><li>' . implode('</li><li>', $folders) . '</li></ul>';
		return $content;
	}

	public function getFolders()
	{
		$folders = [];
		$it = new DirectoryIterator('../');
		foreach ($it as $file) {
			/** @var $file SplFileInfo */
			if ($file->isDir()) {
				$filename = $file->getFilename();
				if ($filename[0] !== '.') {
					$folders[$file->getPathname()] = $file->getFilename();
				}
			}
		}
		return $folders;
	}

}
