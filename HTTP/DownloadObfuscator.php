<?php

/**
 * (c) 2010 Slawa
 * Usage:
 *        $obfuskator = new DownloadObfuscator($this);
 * $attachment = $obfuskator->getDownloadLink();
 */
class DownloadObfuscator
{
	/**
	 * File with relative path (as if downloading directly from browser URL)
	 * @var string
	 */
	protected $file;

	/**
	 * @var string
	 */
	protected $fileSuffix;

	/**
	 * @var string secret code for hash generation
	 */
	protected $swordfish = 's0meS$cretC0de';

	public $linkPrefix = '?';

	/**
	 * Supply the filename and folder
	 *
	 * @param $file
	 * @param string $fileSuffix
	 */
	public function __construct($file = '', $fileSuffix = '')
	{
		$this->file = $file ?: $_REQUEST['file'];
		$this->fileSuffix = $fileSuffix;
		$this->swordfish .= date('Y-m-d-H');
	}

	public function getDownloadLink()
	{
		//$link = '?id='.DownloadObfuscator::page.'&type='.DownloadObfuscator::type.'&file='.urlencode($this->filename).'&check='.$this->getHash();
		//$link = '?id='.DownloadObfuscator::page.'&type='.DownloadObfuscator::type.'&subid='.$this->sub->submission['uid'].'&fileSuffix='.$this->fileSuffix.'&check='.$this->getHash();
		$link = $this->linkPrefix . http_build_query([
				'c' => static::class,
				//'id' => DownloadObfuscator::page,
				//'type' => DownloadObfuscator::type,
				'file' => $this->file,
				'fileSuffix' => $this->fileSuffix,
				'check' => $this->getHash(),
			]);
		$link .= '&/' . basename($this->file);    // http://stackoverflow.com/a/216777
		return $link;
	}

	public function getHash()
	{
		//return md5($this->swordfish.$this->filename);
		//t3lib_div::debug($this->sub->uploadFileLink . $this->swordfish . $this->sub->submission['uid']);
		//return md5($this->getFileNameWithSuffix($this->sub->uploadFileLink) . $this->swordfish . $this->sub->submission['uid']);
		return md5($this->getFileNameWithSuffix($this->file) . $this->swordfish);
	}

	public function getFileNameWithSuffix($file)
	{
		return substr($file, 0, -4) . $this->fileSuffix . substr($file, -4);
	}

	public function checkHash($check)
	{
		//debug($this->getHash(), $check);
		return $this->getHash() == $check;
	}

	/**
	 * Previous name checkAndStreamFile
	 */
	public function render()
	{
		$r = Request::getInstance();
		if ($this->checkHash($r->getTrim('check'))) {
			$this->streamFile();
		} else {
			throw new AccessDeniedException(__('Hash check failed.'));
		}
	}

	public function fileExists($file)
	{
		//$file = str_replace(SUBMISSION_SUB_BASE, '', $file);
		//$file = escapeshellcmd($file);
		// FIX http://ors.nintendo.de/dev/QueueEPES/QueueEPES/RequestInfoEPES?id=97311
		$file = str_replace("''", "'", $file);    // who escaped it?!?
		return file_exists($file) && is_readable($file);
	}

	public function streamFile()
	{
		$file = $this->getFileNameWithSuffix($this->file);
		//$file = str_replace(SUBMISSION_SUB_BASE, '', $file);
		$exists = $this->fileExists($file);
		//debug($file, $exists, glob(dirname($file).'/*')); exit();
		if ($exists) {
			//if ($GLOBALS['TSFE']->fe_user->user['uid']) {
			// urlencode makes is ugly
			header('Content-Disposition: attachment; filename="' ./*urlencode*/
				(basename($file)) . '"');
			header('Content-type: application/force-download');
			header('Content-type: application/octet-stream');
			//header('Content-type: application/x-msdownload'); // Excel?!?
			readfile($file);
			exit();
		} else {
			throw new Exception(__('File does not exist.'));
		}
	}

}
