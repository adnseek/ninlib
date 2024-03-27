<?php

/**
 * Class ExeFile
 * @see https://stackoverflow.com/questions/2029409/get-version-of-exe-via-php
 */
class ExeFile extends File
{

	// http://bytepointer.com/resources/win32_res_format_older.htm
	#define    RT_VERSION          16
	const RT_VERSION = 16;

	/**
	 * @return array|bool
	 * @deprecated
	 */
	public function GetFileVersion()
	{
		$FileName = $this->getPathname();

		$handle = fopen($FileName, 'rb');
		if (!$handle) {
			return false;
		}
		$Header = fread($handle, 64);
		if (substr($Header, 0, 2) !== 'MZ') {
			return false;
		}
		$PEOffset = unpack("V", substr($Header, 60, 4));
		if ($PEOffset[1] < 64) {
			return false;
		}
		fseek($handle, $PEOffset[1], SEEK_SET);
		$Header = fread($handle, 24);
		if (substr($Header, 0, 2) !== 'PE') {
			return false;
		}
		$Machine = unpack("v", substr($Header, 4, 2));
		if ($Machine[1] != 332) {
			return false;
		}
		$NoSections = unpack("v", substr($Header, 6, 2));
		$OptHdrSize = unpack("v", substr($Header, 20, 2));
		fseek($handle, $OptHdrSize[1], SEEK_CUR);
		$ResFound = false;
		$SecHdr = null;
		for ($x = 0; $x < $NoSections[1]; $x++) {      //$x fixed here
			$SecHdr = fread($handle, 40);
			if (substr($SecHdr, 0, 5) === '.rsrc') {         //resource section
				$ResFound = true;
				break;
			}
		}
		if (!$ResFound) {
			return false;
		}

		$SubOff = null;

		$InfoVirt = unpack("V", substr($SecHdr, 12, 4));
		$InfoSize = unpack("V", substr($SecHdr, 16, 4));
		$InfoOff = unpack("V", substr($SecHdr, 20, 4));
		fseek($handle, $InfoOff[1], SEEK_SET);
		$Info = fread($handle, $InfoSize[1]);
		$NumDirs = unpack("v", substr($Info, 14, 2));
		$SubOff = null;
		$InfoFound = false;
		for ($x = 0; $x < $NumDirs[1]; $x++) {
			$Type = unpack("V", substr($Info, ($x * 8) + 16, 4));
			if ($Type[1] == static::RT_VERSION) {             //FILEINFO resource
				$InfoFound = true;
				$SubOff = unpack("V", substr($Info, ($x * 8) + 20, 4));
				break;
			}
		}
		if (!$InfoFound) {
			return false;
		}
		$SubOff[1] &= 0x7fffffff;
		$InfoOff = unpack("V", substr($Info, $SubOff[1] + 20, 4)); //offset of first FILEINFO
		$InfoOff[1] &= 0x7fffffff;
		$InfoOff = unpack("V", substr($Info, $InfoOff[1] + 20, 4));    //offset to data
		$DataOff = unpack("V", substr($Info, $InfoOff[1], 4));
		$DataSize = unpack("V", substr($Info, $InfoOff[1] + 4, 4));
		$CodePage = unpack("V", substr($Info, $InfoOff[1] + 8, 4));
		$DataOff[1] -= $InfoVirt[1];
		$Version = unpack("v4", substr($Info, $DataOff[1] + 48, 8));
		$x = $Version[2];
		$Version[2] = $Version[1];
		$Version[1] = $x;
		$x = $Version[4];
		$Version[4] = $Version[3];
		$Version[3] = $x;
		return $Version;
	}

	public function get_product_version()
	{
		$file_name = $this->getPathname();
		$key = "P\x00r\x00o\x00d\x00u\x00c\x00t\x00V\x00e\x00r\x00s\x00i\x00o\x00n\x00\x00\x00";
		$fptr = fopen($file_name, "rb");
		$data = "";
		while (!feof($fptr)) {
			$data .= fread($fptr, 65536);
			if (strpos($data, $key) !== false) {
				break;
			}
			$data = substr($data, strlen($data) - strlen($key));
		}
		fclose($fptr);
		if (strpos($data, $key) === false) {
			return "";
		}
		$pos = strpos($data, $key) + strlen($key);
		$version = "";
		for ($i = $pos; $data[$i] != "\x00"; $i += 2) {
			$version .= $data[$i];
		}
		return $version;
	}

	public function GetFileVersionSeeking()
	{
		return $this->GetValueOfSeeking('FileVersion');
	}

	public function GetValueOfSeeking($seeking)
	{
		$FileName = $this->getPathname();
		$handle = fopen($FileName, 'rb');
		if (!$handle) return false;
		$Header = fread($handle, 64);

		if (substr($Header, 0, 2) !== 'MZ') return false;

		$PEOffset = unpack("V", substr($Header, 60, 4));
		if ($PEOffset[1] < 64) return false;

		fseek($handle, $PEOffset[1], SEEK_SET);
		$Header = fread($handle, 24);

		if (substr($Header, 0, 2) !== 'PE') return false;

		$Machine = unpack("v", substr($Header, 4, 2));
		if ($Machine[1] != 332) {
			return false;
		}

		$NoSections = unpack("v", substr($Header, 6, 2));
		$OptHdrSize = unpack("v", substr($Header, 20, 2));
		fseek($handle, $OptHdrSize[1], SEEK_CUR);

		$SecHdr = null;
		$ResFound = false;
		for ($x = 0; $x < $NoSections[1]; $x++) {
			//$x fixed here
			$SecHdr = fread($handle, 40);
			if (substr($SecHdr, 0, 5) === '.rsrc') {
				//resource section
				$ResFound = true;
				break;
			}
		}

		if (!$ResFound) {
			return false;
		}
		$InfoVirt = unpack("V", substr($SecHdr, 12, 4));
		$InfoSize = unpack("V", substr($SecHdr, 16, 4));
		$InfoOff = unpack("V", substr($SecHdr, 20, 4));

		fseek($handle, $InfoOff[1], SEEK_SET);
		$Info = fread($handle, $InfoSize[1]);

		$NumNamedDirs = unpack("v", substr($Info, 12, 2));
		$NumDirs = unpack("v", substr($Info, 14, 2));

		$SubOff = null;
		$InfoFound = false;
		for ($x = 0; $x < ($NumDirs[1] + $NumNamedDirs[1]); $x++) {
			$Type = unpack("V", substr($Info, ($x * 8) + 16, 4));
			if ($Type[1] == static::RT_VERSION) {
				//FILEINFO resource
				$InfoFound = true;
				$SubOff = unpack("V", substr($Info, ($x * 8) + 20, 4));
				break;
			}
		}

		if (!$InfoFound) {
			return false;
		}

		if (0) {
			$SubOff[1] &= 0x7fffffff;
			$InfoOff = unpack("V", substr($Info, $SubOff[1] + 20, 4)); //offset of first FILEINFO
			$InfoOff[1] &= 0x7fffffff;
			$InfoOff = unpack("V", substr($Info, $InfoOff[1] + 20, 4));    //offset to data
			$DataOff = unpack("V", substr($Info, $InfoOff[1], 4));
			$DataSize = unpack("V", substr($Info, $InfoOff[1] + 4, 4));
			$CodePage = unpack("V", substr($Info, $InfoOff[1] + 8, 4));
			$DataOff[1] -= $InfoVirt[1];
			$Version = unpack("v4", substr($Info, $DataOff[1] + 48, 8));
			$x = $Version[2];
			$Version[2] = $Version[1];
			$Version[1] = $x;
			$x = $Version[4];
			$Version[4] = $Version[3];
			$Version[3] = $x;

			return $Version;
		}

		//view data...
		//echo print_r(explode("\x00\x00\x00", $Info));
		// could prolly substr on VS_VERSION_INFO
		$encodedKey = implode("\x00", str_split($seeking));
		$StartOfSeekingKey = strpos($Info, $encodedKey);
		if ($StartOfSeekingKey !== false) {
			$ulgyRemainderOfData = substr($Info, $StartOfSeekingKey);
			$ArrayOfValues = explode("\x00\x00\x00", $ulgyRemainderOfData);
			// the key your are seeking is 0, where the value is one
			return trim($ArrayOfValues[1]);
		}

		return false;
	}

}
