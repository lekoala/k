<?php

require '_bootstrap.php';

$session = new k\session\Session();

$msg = $session->take('flash.msg','no message :-(');
echo $msg;
if($msg == 'no message :-(') {
	$session->set('flash.msg','hello world');
}
$session->set('session','value');
$_SESSION['test1'] = 'remembered because session is opened';
session_write_close();
$_SESSION['test2'] = 'not remembered because session was closed';
$session->set('test','remembered because set checks if the session is active or not!');
$session->set('sub.test','sub stuff works too');

echo '<pre>' . __LINE__ . "\n";
print_r($session->get());
exit();