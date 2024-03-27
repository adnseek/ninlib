<?php

class ErrorLog extends AppControllerBE
{

	public $file = '/var/log/apache2/error.log';

	public function render()
	{
		$file = ini_get('error_log');
		$exists = file_exists($file);
		debug($file, $exists);
		if ($exists) {
			$this->file = $file;
		}

		$content[] = "<strong>" . $this->file . "</strong>" . BR;
		$fsize = round(filesize($this->file) / 1024 / 1024, 2);
		$content[] = "File size is {$fsize} megabytes" . BR;
		$lines = $this->read_file($this->file, 50);
		foreach ($lines as $line) {
			$content[] = $line . BR;
		}
		return $content;
	}

	/**
	 * http://tekkie.flashbit.net/php/tail-functionality-in-php
	 * @param string $file
	 * @param int $lines
	 * @return array
	 */
	public function read_file($file, $lines)
	{
		$text = [];
		$handle = fopen($file, "r");
		if ($handle) {
			$linecounter = $lines;
			$pos = -100;
			$beginning = false;
			while ($linecounter > 0) {
				$t = " ";
				while ($t != "\n") {
					if (fseek($handle, $pos, SEEK_END) == -1) {
						$beginning = true;
						break;
					}
					$t = fgetc($handle);
					$pos--;
				}
				$linecounter--;
				if ($beginning) {
					rewind($handle);
				}
				$text[$lines - $linecounter - 1] = fgets($handle);
				if ($beginning) {
					break;
				}
			}
			fclose($handle);
		}
		return array_reverse($text);
	}

}
