<?php

use spidgorny\nadlib\HTTP\URL;

class Path
{
	public $sPath;

	public $aPath;

	public $isDir = true;

	public $isFile = false;

	public $isAbsolute;

	public static function slash($path)
	{
		return str_replace('\\', '/', $path);
	}

	public function __construct($sPath)
	{
		$this->sPath = $sPath . '';
		$this->isDir = str_endsWith($this->sPath, '/');
		$this->isFile = !$this->isDir;
		$this->isAbsolute = $this->isAbsolute();
		$this->explode();
		$this->implode();   // to prevent '//'
	}

	public static function isItAbsolute($sPath)
	{
		return str_startsWith($sPath, '/')        // Linux
			|| (isset($sPath[1]) && $sPath[1] == ':');    // Windows c:
	}

	public function isAbsolute()
	{
		return self::isItAbsolute($this->sPath);
	}

	/**
	 * @param $sPath
	 * @return Path
	 */
	public static function make($sPath)
	{
		$new = new self($sPath);
		return $new;
	}

	/**
	 * Modifies the array path after string modification
	 */
	public function explode()
	{
		$forwardSlash = str_replace('\\', '/', $this->sPath);
		$this->aPath = trimExplode('/', $forwardSlash);
	}

	/**
	 * Modifies the string path after array modification
	 */
	public function implode()
	{
		$notSlash = $this->aPath != ['/'];
		if ($this->isWindows()) {
			$prefix = '';
		} else {
			$prefix = (($this->isAbsolute && $notSlash) ? '/' : '');
		}
		$this->sPath = $prefix . implode('/', $this->aPath);
		if ($this->isDir && sizeof($this->aPath)) {    // avoid "//"
			$this->sPath .= '/';
		}
		return $this->sPath;
	}

	/**
	 * Check for ":" in C:...
	 * @return bool
	 */
	public function isWindows()
	{
		return (isset($this->aPath[0][1]) && $this->aPath[0][1] == ':');
	}

	public function __toString()
	{
		return $this->isDir ? $this->getCapped() : $this->getUncapped();
	}

	/**
	 * @param string $dirname
	 * @return bool
	 */
	public function contains($dirname)
	{
		return in_array($dirname, $this->aPath);
	}

	/**
	 * @param Path $plus
	 * @return $this
	 */
	public function append(Path $plus)
	{
		if (defined('DEVELOPMENT') && DEVELOPMENT) {
			//debug($this->aPath, $plus->aPath);
		}
		foreach ($plus->aPath as $name) {
			if ($name === '.') {
				continue;
			}

			if ($name === '..') {
				array_pop($this->aPath);
			} else {
				$this->aPath[] = $name;
			}
		}
		$this->isDir = $plus->isDir;
		$this->isFile = $plus->isFile;
		$this->implode();
		return $this;
	}

	/**
	 * @param string $plus
	 * @return $this
	 */
	public function appendString($plus)
	{
		$pPlus = new Path($plus);
		$this->append($pPlus);
		return $this;
	}

	/**
	 * @param string $plus
	 * @return $this
	 */
	public function prependString($plus)
	{
		$pPlus = new Path($plus);
		$this->aPath = array_merge($pPlus->aPath, $this->aPath);
		return $this;
	}

	/**
	 * @param $plus string|Path
	 * @return bool
	 */
	public function appendIfExists($plus)
	{
		$pPlus = $plus instanceof Path
			? $plus
			: new Path($plus);
		$new = clone $this;
		$new->append($pPlus);
		//debug($pPlus, $new, $new->exists());
		if ($new->exists()) {
			$this->aPath = $new->aPath;
			$this->sPath = $new->sPath;
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function exists()
	{
		if (ini_get('open_basedir')) {
			return false;
		}
		return is_dir($this->sPath) || file_exists($this->sPath);
	}

	public function trim()
	{
		array_pop($this->aPath);
		$this->implode();
	}

	public function trimIf($dirname)
	{
		if (end($this->aPath) == $dirname) {
			$this->trim();
		}
	}

	public function getUncapped()
	{
		return $this->implode();
	}

	public function getCapped()
	{
		if (!sizeof($this->aPath) && $this->isAbsolute()) {
			return $this->implode();    // absolute empty has slash already
		}
		return cap($this->implode());
	}

	/**
	 * @param $with
	 * @return bool
	 */
	public function ends($with)
	{
		return end($this->aPath) == $with;
	}

	/**
	 * @return self
	 */
	public function up()
	{
		if ($this->aPath) {
			array_pop($this->aPath);
		} else {
			array_push($this->aPath, '..');
		}
		$this->implode();
		return $this;
	}

	/**
	 * @param $that
	 * @return self
	 */
	public function upIf($that)
	{
		if (end($this->aPath) == $that) {
			return $this->up();
		}
		return $this;
	}

	/**
	 * @param $that
	 * @return self
	 */
	public function upIfNot($that)
	{
		if (end($this->aPath) != $that) {
			return $this->up();
		}
		return $this;
	}

	/**
	 * Removes path elements from the BEGINNING of the path
	 * @param $minus
	 * @return $this
	 */
	public function remove($minus)
	{
		$minus = $minus instanceof Path ? $minus : new Path($minus);
		foreach ($minus->aPath as $i => $sub) {
			if (ifsetor($this->aPath[0]) == $sub) {  // 0 because shift
				array_shift($this->aPath);
			} else {
				break;
			}
		}
		// remove empty paths which lead to //
		$this->aPath = array_filter($this->aPath);
		$this->implode();
		return $this;
	}

	/**
	 * Removes path elements from the BEGINNING of the path.
	 * Symlinks are considered identical.
	 * @param $minus
	 * @return $this
	 */
	public function removeWithLinks($minus)
	{
		$minus = $minus instanceof Path ? $minus : new Path($minus);
		foreach ($minus->aPath as $i => $sub) {
			$sameName = ifsetor($this->aPath[0]) == $sub;
			d($this->sPath);
			if (is_link($this->sPath)) {
				$linkTarget = readlink($this->sPath);
			} else {
				$linkTarget = $this->sPath;
			}
			d(__METHOD__, $linkTarget, $minus->sPath);
			$sameLink = $linkTarget == $minus->sPath;
			if ($sameName || $sameLink) {
				array_shift($this->aPath);
				$this->implode();
			} else {
				break;
			}
		}
		$this->implode();
		return $this;
	}

	public function reverse()
	{
		if (!$this->isAbsolute() && !empty($this->aPath)) {
			$this->aPath = array_fill(0, sizeof($this->aPath), '..');
			$this->implode();
		}
		return $this;
	}

	public function resolveLink()
	{
		if (is_link($this->sPath)) {
			$this->sPath = readlink($this->sPath);
			$this->explode();
		}
		$this->realPath();
	}

	/**
	 * Recursive. For no reason?
	 * Reason is that readlink() fails if the final part is not a link
	 * @return $this
	 */
	public function resolveLinks()
	{
		foreach ($this->aPath as $i => $part) {
			$assembled = '/' . implode('/', array_slice($this->aPath, 0, $i));
			//			debug($assembled, is_link($assembled));
			if (@is_link($assembled)) {
				$this->sPath = readlink($assembled);
				$this->explode();
				$this->resolveLinks();
				break;
			}
		}
		return $this;
	}

	public function resolveLinksSimple()
	{
		foreach ($this->aPath as $i => $part) {
			$assembled = '/' .
				implode('/', array_slice($this->aPath, 0, $i));
			//			debug($assembled, is_link($assembled));
			if (@is_link($assembled)) {
				$this->aPath[$i - 1] = trim(readlink($assembled), '/');
			}
		}
		$this->aPath = array_filter($this->aPath);
		$this->implode();
		return $this;
	}

	public function onlyExisting()
	{
		$assembled = '';
		foreach ($this->aPath as $i => $part) {
			$assembled = '/' .
				implode('/', array_slice($this->aPath, 0, $i + 1));
			if (!is_dir($assembled)) {
				break;
			}
		}
		$this->__construct($assembled);
		$this->up();
		return $this;
	}

	/**
	 * @return Path
	 */
	public function relativeFromDocRoot()
	{
		$this->makeAbsolute();
		$al = AutoLoad::getInstance();

		// will cut duplicates
		//$new = array_diff($this->aPath, $al->documentRoot->aPath);
		$new = $this->cutArrayFromArray($this->aPath, $al->documentRoot->aPath);

		//debug($this->aPath, $al->documentRoot->aPath, $new);
		$relative = Path::fromArray($new);
		$relative->isFile = $this->isFile;
		$relative->isDir = $this->isDir;
		return $relative;
	}

	/**
	 * @return Path
	 */
	public function relativeFromAppRoot()
	{
		$this->makeAbsolute();
		$al = AutoLoad::getInstance();
		$appRoot = $al->getAppRoot();
		if ($appRoot) {
			$new = $this->cutArrayFromArray($this->aPath, $appRoot->aPath);
			//			debug(json_encode($this->aPath), json_encode($al->getAppRoot()->aPath), json_encode($new));
			$relative = Path::fromArray($new);
			$relative->isFile = $this->isFile;
			$relative->isDir = $this->isDir;
		} else {
			$relative = $this;
		}
		return $relative;
	}

	public function makeAbsolute()
	{
		if (!$this->isAbsolute()) {
			//debug(getcwd(), $this);
			$prefix = new Path(getcwd());
			//debug($prefix);
			$prefix->append($this);
			$this->aPath = $prefix->aPath;
			$this->implode();
			$this->checkFileDir();
		}
	}

	/**
	 * This may not be isFile() and isDir() functions
	 * because we can force it to be a file or dir
	 * with setAsFile() and setAsDir().
	 */
	public function checkFileDir()
	{
		$this->isFile = is_file($this->sPath);
		$this->isDir = is_dir($this->sPath);
	}

	/**
	 * It should not cap() the result, we don't know if it's a file or dir
	 * @return Path|string
	 */
	public function getURL()
	{
		//$self = new Path(AutoLoad::getInstance()->appRoot);
		$self = new Path(URL::getScriptWithPath());
		//debug($self, basename($this->sPath), $this->sPath);
		if ($self->contains(basename($this->sPath))) {
			$relative = new Path(URL::getRelativePath($self, $this));
			//			$relative->setAsDir();
			//debug(__METHOD__, $this . '', $self . '', $relative . '');
		} else {
			$relative = URL::getRelativePath($self, $this->sPath);
		}
		return $relative;
	}

	public static function fromArray(array $parts)
	{
		$path = new Path('');
		$path->aPath = $parts;
		$path->implode();
		return $path;
	}

	public function setFile($name)
	{
		if ($this->isFile) {
			$this->aPath[sizeof($this->aPath) - 1] = $name;
		} else {
			$this->aPath[] = $name;
		}
		$this->implode();
	}

	public function setAsDir()
	{
		$this->isDir = true;
		$this->isFile = false;
	}

	public function setAsFile()
	{
		$this->isDir = false;
		$this->isFile = true;
	}

	public function length()
	{
		return sizeof($this->aPath);
	}

	public function getDebug()
	{
		return [
			'sPath' => $this->sPath,
			'aPath' => $this->aPath,
			'isAbsolute' => $this->isAbsolute(),
			'isDir' => $this->isDir,
			'isFile' => $this->isFile,
			'exists' => $this->exists(),
		];
	}

	public function getLevels()
	{
		return $this->aPath;
	}

	public function getNameless($i)
	{
		return $this->aPath[$i];
	}

	/**
	 * @param array $long
	 * @param array $short
	 * @return array
	 * @internal param $al
	 */
	private function cutArrayFromArray(array $long, array $short)
	{
		$new = [];
		$different = false;
		foreach ($long as $key => $value) {
			$other = ifsetor($short[$key]);
			if ($value != $other) {
				$different = true;
			}
			if ($different) {
				$new[] = $value;
			}
		}
		return $new;
	}

	/**
	 * http://php.net/manual/en/function.realpath.php#112367
	 * @return string
	 */
	public function getNormalized()
	{
		$path = $this->__toString();
		$parts = [];// Array to build a new path from the good parts
		$path = str_replace('\\', '/', $path);// Replace backslashes with forwardslashes
		$path = preg_replace('/\/+/', '/', $path);// Combine multiple slashes into a single slash
		$segments = explode('/', $path);// Collect path segments
		$test = '';// Initialize testing variable
		foreach ($segments as $segment) {
			if ($segment != '.') {
				$test = array_pop($parts);
				if (is_null($test)) {
					$parts[] = $segment;
				} elseif ($segment == '..') {
					if ($test == '..') {
						$parts[] = $test;
					}

					if ($test == '..' || $test == '') {
						$parts[] = $segment;
					}
				} else {
					$parts[] = $test;
					$parts[] = $segment;
				}
			}
		}
		$parts = array_filter($parts);    // avoid "//"
		$prefix = $this->isAbsolute() ? '/' : '';
		return $prefix . implode('/', $parts);
	}

	public function normalize()
	{
		$this->__construct($this->getNormalized());
		return $this;
	}

	public function getFiles()
	{
		$files = glob(cap($this->sPath) . '*');
		$basenames = array_map(function ($file) {
			return basename($file);
		}, $files);
		$files = array_combine($basenames, $files);
		return $files;
	}

	public function hasFile($file)
	{
		$files = $this->getFiles();
		//debug($files);
		return !!ifsetor($files[$file]);
	}

	public function debugPathExists()
	{
		$debug = [];
		$sPath = $this->isAbsolute() ? '/' : '';
		foreach ($this->aPath as $i => $section) {
			$sPath .= $section;
			if ($i < sizeof($this->aPath)) {
				$sPath .= '/';
			}
			$debug[$sPath] = file_exists($sPath);
		}
		debug($debug);
	}

	public function realPath()
	{
		$this->sPath = realpath($this->sPath);
		$this->explode();
		return $this;
	}

	public function normalizeHomePage()
	{
		$this->realPath();
		//debug(__METHOD__, $this->sPath, $this->aPath);
		$this->resolveLinks();        // important to avoid differences
		foreach ($this->aPath as $i => $el) {
			if ($el[0] == '~') {
				$username = str_replace('~', '', $el);
				array_splice($this->aPath, $i, 1, [$username, 'public_html']);
				//				debug($el, $username, $this->aPath);
			}
		}
		$this->implode();
		return $this;
	}

	public function basename()
	{
		return end($this->aPath);
	}
}
