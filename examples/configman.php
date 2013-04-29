<?php
define('SRC_PATH',realpath('../src'));
require SRC_PATH . '/K/init.php';

$config = new K\Config('data/man.config.php');
$man = new K\ConfigManager($config,true);
echo $man;