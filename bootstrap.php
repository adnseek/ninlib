<?php

use spidgorny\nadlib\Bootstrap;

require_once __DIR__ . '/Base/Bootstrap.php';

(new Bootstrap())->boot();

// run this after bootstrapping
//Config::getInstance()->postInit();
