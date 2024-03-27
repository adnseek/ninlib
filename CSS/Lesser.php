<?php

/**
 * composer require ca-dsgn/lessphp
 */
class Lesser extends AppControllerBE
{

	public static $cacheFolder;

	/**
	 * No auth needed
	 * @var bool
	 */
	public static $public = true;
	public $layout = 'none';
	protected $output = 'cache/merge.css';

	public function __construct()
	{
		if (!ifsetor($_REQUEST['d'])) {
			unset($_COOKIE['debug']);
		} else {
			$_COOKIE['debug'] = 1;
		}
		parent::__construct();
		$appRoot = AutoLoad::getInstance()->getAppRoot();
		$this->output = Path::make($appRoot)->appendString($this->output);
		$cacheDir = dirname($this->output);
		nodebug([
			'lessc' => class_exists('lessc'),
			'appRoot' => $appRoot,
			'output' => $this->output,
			'cacheDir' => $cacheDir,
			'file_exists()' => file_exists($cacheDir),
			'is_dir()' => is_dir($cacheDir),
			'is_writable()' => is_writable($cacheDir)
		], DebugHTML::LEVELS, 5);
		if (!is_dir($cacheDir)) {
			echo '#mkdir(', $cacheDir, ');' . "\n";
			$ok = mkdir($cacheDir);
			if (!$ok) {
				throw new RuntimeException('Cache dir not existing, can not be created. ' . $cacheDir);
			}
		}
		@set_time_limit(30);  // compiling bootstrap
	}

	public function render()
	{
		session_write_close();
		//$less->importDir[] = '../../';
		$cssFile = $this->request->getFilePathName('css');
		if (!$cssFile) {
			$cssFile = 'public/' . $this->request->getTrim('css');
			if (!file_exists($cssFile)) {
				$cssFile = new Path(AutoLoad::getInstance()->documentRoot);
				$cssFile->appendString($_SERVER['REQUEST_URI']);    // rewrite_rule
				$cssFile = $cssFile->getUncapped();
				//echo $cssFile.BR;
			}
		}
		if ($cssFile) {
			$cssFileName = $this->request->getFilename('css');
			$this->output = dirname($this->output) . '/' . str_replace('.less', '.css', $cssFileName);
			//debug($cssFile, $cssFileName, file_exists($cssFile), $this->output);

			if (!headers_sent()) {
				header("Date: " . gmdate("D, d M Y H:i:s", time()) . " GMT");
				header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()) . " GMT");
				header("Expires: " . gmdate("D, d M Y H:i:s", time() + 60 * 60 * 24) . " GMT");
				header('Pragma: cache');
				header_remove('Cache-control');
			}

			$ext = pathinfo($cssFile, PATHINFO_EXTENSION);
			if ($ext == 'less') {
				$compiledNearby = str_replace('.less', '.css', $cssFile);
				if (file_exists($compiledNearby)) {
					@header('Content-type: text/css');
					echo file_get_contents($cssFile);
					exit();
				} elseif (file_exists('vendor/oyejorge/less.php/lessc.inc.php')) {
					require_once 'vendor/oyejorge/less.php/lessc.inc.php';
					$less = new lessc();
					if (is_writable($this->output)) {
						if ($this->request->isCtrlRefresh()) {
							$less->compileFile($cssFile, $this->output);
						} else {
							$less->checkedCompile($cssFile, $this->output);
						}

						if (file_exists($this->output)) {
							header('Content-type: text/css');
							readfile($this->output);
						} else {
							echo 'error {}';
						}
					} else {
						@header('Content-type: text/css');
						echo $less->compileFile($cssFile);
					}
				} else {
					echo 'composer require oyejorge/less.php';
				}
			} else {
				@header('Content-type: text/css');
				echo file_get_contents($cssFile);
				exit();
			}
		} else {
			echo getDebug($_REQUEST);
			echo 'error which file?';
		}
		$this->request->set('ajax', true);    // avoid any HTML
	}

}
