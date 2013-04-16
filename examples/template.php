<?php
define('SRC_PATH',realpath('../src'));
require SRC_PATH . '/K/init.php';

K\Template::setGlobals('global','global value');

$filename = __DIR__ . '/data/templ';
$layoutFilename = __DIR__ . '/data/layout';
$vars = 'test';
//$templ = new K\Template($filename, $vars);
$templ = new K\Template(array($filename, $layoutFilename), $vars);
echo $templ;