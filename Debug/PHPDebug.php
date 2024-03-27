<?php

class PHPDebug
{

	public function __construct()
	{
		echo '<script type="text/javascript">' . NL;

		/// this is for IE and other browsers w/o console
		echo 'if (!window.console) console = {};';
		echo 'console.log = console.log || function(){};';
		echo 'console.warn = console.warn || function(){};';
		echo 'console.error = console.error || function(){};';
		echo 'console.info = console.info || function(){};';
		echo 'console.debug = console.debug || function(){};';
		echo '</script>';
		/// end of IE
	}

	public function debug($name, $var = null, $type = LOG)
	{
		echo '<script type="text/javascript">' . NL;
		switch ($type) {
			case LOG:
				echo 'console.log("' . $name . '");' . NL;
				break;
			case INFO:
				echo 'console.info("' . $name . '");' . NL;
				break;
			case WARN:
				echo 'console.warn("' . $name . '");' . NL;
				break;
			case ERROR:
				echo 'console.error("' . $name . '");' . NL;
				break;
		}

		if (!empty($var)) {
			if (is_object($var) || is_array($var)) {
				//echo '</script>'; debug($var);
				$object = json_encode($var);
				//debug($object); exit();
				$jsName = preg_replace('~[^A-Z|0-9]~i', "_", $name);
				echo 'var object' . $jsName . ' = \'' . str_replace("'", "'", $object) . '\';' . NL;
				echo 'var val' . $jsName . ' = eval("(" + object' . $jsName . ' + ")" );' . NL;
				switch ($type) {
					case LOG:
						echo 'console.debug(val' . $jsName . ');' . NL;
						break;
					case INFO:
						echo 'console.info(val' . $jsName . ');' . NL;
						break;
					case WARN:
						echo 'console.warn(val' . $jsName . ');' . NL;
						break;
					case ERROR:
						echo 'console.error(val' . $jsName . ');' . NL;
						break;
				}
			} else {
				switch ($type) {
					case LOG:
						echo 'console.debug("' . str_replace('"', '\"', $var) . '");' . NL;
						break;
					case INFO:
						echo 'console.info("' . str_replace('"', '\"', $var) . '");' . NL;
						break;
					case WARN:
						echo 'console.warn("' . str_replace('"', '\"', $var) . '");' . NL;
						break;
					case ERROR:
						echo 'console.error("' . str_replace('"', '\"', $var) . '");' . NL;
						break;
				}
			}
		} else {
			echo 'console.log("<empty>");' . NL;
		}
		echo '</script>' . NL;
	}

}
