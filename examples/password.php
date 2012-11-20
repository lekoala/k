<?php
define('SRC_PATH', realpath('../src'));
require SRC_PATH . '/K/init.php';
require '../vendor/autoload.php';


use K\Password;

echo Password::hash('mypass');