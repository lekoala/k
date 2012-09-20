<?php
define('SRC_PATH',realpath('../src'));
require SRC_PATH . '/K/init.php';

K\DebugBar::init();

$msg = K\Session::take('flash.msg','no message :-(');
echo $msg;
if($msg == 'no message :-(') {
	K\Session::set('flash.msg','hello world');
}
K\Session::set('session','value');
$_SESSION['test1'] = 'remembered because session is opened';
session_write_close();
$_SESSION['test2'] = 'not remembered because session was closed';
K\Session::set('test','remembered because set checks if the session is active or not!');
K\Session::set('sub.test','sub stuff works too');

echo '<pre>' . __LINE__ . "\n";
print_r(K\Session::get());
exit();