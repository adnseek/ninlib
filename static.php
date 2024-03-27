<?php /** @noinspection ALL */

// this file is used to help phpstan understand some undefined constants

if (!defined('BR')) {
	define('BR', "<br />\n");
}
if (!defined('TAB')) {
	define('TAB', "\t");
}
if (!defined('DEVELOPMENT')) {
	define('DEVELOPMENT', true);
}
if (!defined("LOG")) {
	define("LOG", 1);
}
if (!defined("INFO")) {
	define("INFO", 2);
}
if (!defined("WARN")) {
	define("WARN", 3);
}
if (!defined("ERROR")) {
	define("ERROR", 4);
}

define("NL", "\r\n");
