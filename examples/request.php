<?php
define('SRC_PATH',realpath('../src'));
require SRC_PATH . '/K/init.php';

K\DebugBar::init();

use K\Request as req;

echo req::ip();
echo '<br/>';
echo req::domain();
echo '<br/>';
var_dump(req::isAjax());
echo '<br/>';
var_dump(req::isMobile());
echo '<br/>';
echo req::lang();
echo '<br/>';
echo req::method();
echo '<br/>';
var_dump(req::referer());
echo '<br/>';
print_r(req::params());
echo '<br/>';
print_r(req::data());