<?php

// PHP Server Info - A PHP Server Information Script
// http://freewebs.com/phpstatus/
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// Mod by Slawa.

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/HTML/BarImage.php';

if (!function_exists('imagecreate')) {
	error_reporting(ifsetor($_COOKIE['debug']) ? E_ALL : 0);
	ini_set('display_errors', true);
	echo 'PHP: ' . phpversion() . '<br />';
	echo 'GD not installed';
} elseif (str_contains(getenv('PHP_SELF'), 'phpstan')) {
} else {
	$bar = new BarImage();
	$bar->setHeaders();
	$bar->drawRating(min(100, ifsetor($_GET['rating'])));
}
