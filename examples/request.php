<?php
define('SRC_PATH',realpath('../src'));
require '../vendor/autoload.php';
require SRC_PATH . '/K/init.php';
K\DebugBar::init();

$request = K\Request::createFromGlobals();

echo 'method : ' . $request->getMethod();

