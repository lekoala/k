<?php
define('SRC_PATH',realpath('../src'));
require SRC_PATH . '/K/init.php';

$config = new \K\Config('data/sample.config.php');
$config->load(array(
	'extra_value' => 'test',
	'local_array' => array('extra' => 'value')
));

\K\System::configure($config);

//error reporting set to off
fsddfs();