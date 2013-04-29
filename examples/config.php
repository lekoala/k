<?php
define('SRC_PATH',realpath('../src'));
require SRC_PATH . '/K/init.php';

$config = new K\Config('data/sample.config.php');
$config->load(array(
	'extra_value' => 'test',
	'local_array' => array('extra' => 'value')
));

echo '<pre>';

print_r($config);

foreach($config as $k => $v) {
	echo $k . ' : ' ;
	print_r($v) ;
	echo '<br/>';
}