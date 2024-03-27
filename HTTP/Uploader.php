<?php

use League\Flysystem\Filesystem;

/**
 * Class Uploader
 * General validating uploader with error handling
 */
class Uploader
{

	/**
	 * Allowed extensions
	 * @var array|null
	 */
	public $allowed = [
		'gif', 'jpg', 'png', 'jpeg',
	];

	/**
	 * Allowed mime types, not checked if empty
	 * @var array
	 */
	public $allowedMime = [];

	public $errors = [
		1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
		'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
		'The uploaded file was only partially uploaded.',
		'No file was uploaded.',
		6 => 'Missing a temporary folder.',
		'Failed to write file to disk.',
		'A PHP extension stopped the file upload.'
	];

	/**
	 *
	 * @param array|null $allowed If provided this will override allowed extensions
	 */
	public function __construct($allowed = [])
	{
		if (!empty($allowed)) {
			$this->allowed = $allowed;
		}

		if ($this->isUploaded()) {
			//debug($_FILES);
		}
	}

	public function isUploaded()
	{
		$uploaded = !!$_FILES;
		$firstFile = first($_FILES);
		if (!$firstFile) {
			return false;
		}
		$uploadName = ifsetor($firstFile['name']);
		if (is_array($uploadName)) {
			$_FILES = $this->GetPostedFiles();
		}
		return $uploaded;
	}

	/**
	 * Usage:
	 * $uf = new Uploader();
	 * $f = $uf->getUploadForm()
	 * // add custom hidden fields to upload form (e.g. Loan[id])
	 * if (!empty($hiddenFields)) {
	 * foreach ($hiddenFields as $name => $value) {
	 *        $f->hidden($name, $value);
	 * }
	 * }
	 * @param  string - input field name - usually 'file'
	 * @return HTMLForm
	 * @param string $fieldName
	 * @return HTMLForm
	 */
	public function getUploadForm($fieldName = 'file')
	{
		$f = new HTMLForm();
		$f->file($fieldName);
		$f->text('<br />');
		$f->submit('Upload', ['class' => 'btn btn-primary']);
		$f->text($this->getLimitsDiv());
		return $f;
	}

	public function getLimitsDiv()
	{
		$tmpDir = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
		if (is_writable($tmpDir)) {
			$tmpDir .= ' [Writable]';
		} else {
			$tmpDir .= ' [Not writable]';
		}
		$upload_max_filesize = ini_get('upload_max_filesize');
		$post_max_size = ini_get('post_max_size');
		return '
		<div class="message">
		    <table>
		        <tr><td><nobr>Uploads enabled:</nobr></td>
		        <td>' . (ini_get('file_uploads') ? 'Yes' : 'No') . '</td></tr>

		        <tr><td><nobr>Max size:</nobr></td>
		        <td title="upload_max_filesize: ' . $upload_max_filesize . '
post_max_size: ' . $post_max_size . '">' .
			min($upload_max_filesize, $post_max_size) . '</td></tr>

		        <tr><td><nobr>Free space:</nobr></td>
		        <td>' .
			number_format(disk_free_space('.') / 1024 / 1024, 0, '.', '') . 'M
		        </td></tr>

		        <tr><td><nobr>Allowed:</nobr></td>
		        <td>' . ($this->allowed
				? implode(', ', $this->allowed)
				: '*.*') . '
				</td></tr>

		        <tr><td><nobr>Temp folder:</nobr></td>
		        <td title="' . $tmpDir . '">' . basename($tmpDir) . '</td></tr>
		    </table>
		</div>
		';
	}

	public function getLimits()
	{
		return [
			'upload_max_filesize' => ini_get('upload_max_filesize'),
			'post_max_size' => ini_get('post_max_size'),
			'disk_free_space' => round(disk_free_space('.') / 1024 / 1024) . 'MB',
		];
	}

	/**
	 * @param $file
	 * @throws UploadException
	 */
	public function validateEverything($from)
	{
		if (is_array($from)) {
			$uf = $from;            // $_FILES['whatever']
		} else {
			$uf = $_FILES[$from];   // string index
		}
		if (!$uf) {
			throw new UploadException("[{$from}] is not a valid $_FILES index");
		}
		if (!$this->checkError($uf)) {
			throw new UploadException($this->errors[$uf['error']]);
		}
		if (!$this->checkExtension($uf)) {
			throw new UploadException('File extension is not allowed (' . $uf['ext'] . ')');
		}
		if (!$this->checkMime($uf)) {
			throw new UploadException('File mime-type is not allowed (' . $uf['mime'] . ')');
		}
	}

	public function getFinalDestination($from, $to, $overwriteExistingFile = true)
	{
		if (is_array($from)) {
			$uf = $from;            // $_FILES['whatever']
		} else {
			$uf = $_FILES[$from];   // string index
		}
		// if you don't want existing files to be overwritten,
		// new file will be renamed to *_n,
		// where n is the number of existing files
		$hasExtension = pathinfo($to, PATHINFO_EXTENSION);
		if (is_dir($to) || !$hasExtension) {
			$fileName = rtrim($to, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $uf['name'];
		} else {
			$fileName = $to;
		}
		if (!is_dir(dirname($fileName))) {
			@mkdir(dirname($fileName), 0777, true);
		}
		if (!$overwriteExistingFile && file_exists($fileName)) {
			$actualName = pathinfo($fileName, PATHINFO_FILENAME);
			$originalName = $actualName;
			$extension = pathinfo($fileName, PATHINFO_EXTENSION);

			$i = 1;
			while (file_exists($to . $actualName . "." . $extension)) {
				$actualName = (string)$originalName . '_' . $i;
				$fileName = $to . $actualName . "." . $extension;
				$i++;
			}
		}
		return $fileName;
	}
	/**
	 * @param string|array $from - usually 'file' - the same name as in getUploadForm()
	 * @param string $to - directory
	 * @param bool $overwriteExistingFile
	 * @return bool
	 * @throws Exception
	 */
	public function moveUpload($from, $to, $overwriteExistingFile = true)
	{
		if (is_array($from)) {
			$uf = $from;            // $_FILES['whatever']
		} else {
			$uf = $_FILES[$from];   // string index
		}
		$this->validateEverything($from);

		$fileName = $this->getFinalDestination($from, $to, $overwriteExistingFile);
		if (!is_dir(dirname($fileName))) {
			@mkdir(dirname($fileName), 0777, true);
		}
		$ok = move_uploaded_file($uf['tmp_name'], $fileName);
		if (!$ok) {
			//throw new Exception($php_errormsg);	// empty
			$error = error_get_last();
			pre_print_r(__METHOD__, $error);
			throw new UploadException($error['message']);
		}
		return $ok;
	}

	/**
	 * @param $from
	 * @param Filesystem $path
	 * @param null $fileName
	 * @return bool
	 * @throws UploadException
	 */
	public function moveUploadFly($from, League\Flysystem\Filesystem $path, $fileName = null)
	{
		if (is_array($from)) {
			$uf = $from;            // $_FILES['whatever']
		} else {
			$uf = $_FILES[$from];   // string index
		}
		if (!$uf) {
			throw new UploadException("[{$from}] is not a valid $_FILES index");
		}
		if (!$this->checkError($uf)) {
			throw new UploadException($this->errors[$uf['error']]);
		}
		if (!$this->checkExtension($uf)) {
			throw new UploadException('File extension is not allowed (' . $uf['ext'] . ')');
		}
		if (!$this->checkMime($uf)) {
			throw new UploadException('File mime-type is not allowed (' . $uf['mime'] . ')');
		}

		if (!$fileName) {
			$fileName = basename($uf['name']);
		}

		$fp = fopen($uf['tmp_name'], 'r+');
		$ok = $path->writeStream($fileName, $fp);
		if (is_resource($fp)) {
			fclose($fp);
		}
		return $ok;
	}

	public function checkError(array $uf)
	{
		$errorCode = $uf['error'];
		return (!$errorCode);
	}

	/**
	 * Only if $this->allowed is defined
	 * @param array $uf
	 * @return bool
	 */
	public function checkExtension(array &$uf)
	{
		if ($this->allowed) {
			$filename = $uf['name'];
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			$uf['ext'] = $ext;
			//debug($uf);
			return in_array(strtolower($ext), $this->allowed);
		} else {
			return true;
		}
	}

	/**
	 * Only if $this->allowedMime is defined
	 * @param array $uf
	 * @return bool
	 */
	public function checkMime(array &$uf)
	{
		if ($this->allowedMime) {
			$mimer = new MIME();
			$mime = $mimer->get_mime_type($uf['tmp_name']);
			$uf['mime'] = $mime;
			//debug($mime, $this->allowedMime);
			return in_array($mime, $this->allowedMime);
		}
		return true;
	}

	/**
	 * Will incrementally create subfolders which don't exist.
	 * @param $folder
	 */
	public function createUploadFolder($folder)
	{
		$parts = trimExplode('/', $folder);
		$current = '/';
		foreach ($parts as $plus) {
			$current .= $plus . '/';
			if (!file_exists($current)) {
				mkdir($current);
			}
		}
	}

	public function getContent($from)
	{
		$uf = $_FILES[$from];
		if ($uf) {
			if ($uf['tmp_name']) {
				return file_get_contents($uf['tmp_name']);
			}
		}
		return null;
	}

	public function getTempFile($fieldName = 'file')
	{
		if ($this->isUploaded()) {
			return $_FILES[$fieldName]['tmp_name'];
		}
		return null;
	}

	public function getBasename($fieldName = 'file')
	{
		if ($this->isUploaded()) {
			return $_FILES[$fieldName]['name'];
		}
		return null;
	}

	/**
	 * Handles the file upload from https://github.com/blueimp/jQuery-File-Upload/wiki/Basic-plugin
	 * If no error it will call a callback to retrieve a redirect URL
	 * @param $callback
	 * @param array $params
	 */
	public function handleBlueImpUpload($callback, array $params)
	{
		$uh = new UploadHandler($params, false);
		//$uh->post(true); exit();
		ob_start();
		$uh->post(true);
		$done = ob_get_clean();
		$json = json_decode($done);
		//print_r(array($uh, $done, $json));
		if (is_object($json)) {
			$data = get_object_vars($json->file[0]);
			if (!ifsetor($data['error'])) {
				$redirect = $callback($data);
				$json->file[0]->redirect = $redirect;
				$request = Request::getInstance();
				if ($request->isAjax()) {
					echo json_encode($json);
				} elseif ($redirect) {
					$request->redirect($redirect);
				}
			} else {
				echo $data['error'];
			}
		} else {
			echo $done;
		}
		exit();
	}

	/**
	 * @brief get the POSTed files in a more usable format
	 * Works on the following methods:
	 * <form method="post" action="/" name="" enctype="multipart/form-data">
	 * <input type="file" name="photo1" />
	 * <input type="file" name="photo2[]" />
	 * <input type="file" name="photo2[]" />
	 * <input type="file" name="photo3[]" multiple />
	 * @param array $source = $_FILES
	 * @return   Array
	 * @todo
	 * @see  http://stackoverflow.com/questions/5444827/how-do-you-loop-through-files-array
	 */
	public static function GetPostedFiles($source = null)
	{
		/* group the information together like this example
		Array
		(
			[attachments] => Array
			(
				[0] => Array
				(
					[name] => car.jpg
					[type] => image/jpeg
					[tmp_name] => /tmp/phpe1fdEB
					[error] => 0
					[size] => 2345276
				)
			)
			[jimmy] => Array
			(
				[0] => Array
				(
					[name] => 1.jpg
					[type] => image/jpeg
					[tmp_name] => /tmp/phpx1HXrr
					[error] => 0
					[size] => 221041
				)
				[1] => Array
				(
					[name] => 2 ' .jpg
					[type] => image/jpeg
					[tmp_name] => /tmp/phpQ1clPh
					[error] => 0
					[size] => 47634
				)
			)
		)
		*/

		$source = is_null($source) ? $_FILES : $source;

		$Result = [];

		foreach ($source as $Field => $Data) {
			foreach ($Data as $Key => $Val) {
				$Result[$Field] = [];
				if (!is_array($Val)) {
					$Result[$Field] = $Data;
				} elseif (isset($Data['name'])) {
					$Result[$Field] = self::GPF_FilesFlip($Data);
				} else {
					$Result[$Field] = $Data;
				}
			}
		}

		return $Result;
	}

	// helper method for GetPostedFiles
	private static function GPF_FilesFlip(array $Value)
	{
		$Result = [];
		foreach ($Value['name'] as $K => $V) {
			$Result[$K] = [];
			foreach ($Value as $param => $set) {
				$Result[$K][$param] = $set[$K];
			}
		}
		return $Result;
	}

	public function getError($code)
	{
		$message = $this->errors[$code];
		if ($code == 1) {
			$message .= ' ['.ini_get('upload_max_filesize').']';
		}
		return $message;
	}

}
