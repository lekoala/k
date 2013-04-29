<?php
define('SRC_PATH',realpath('../src'));
require SRC_PATH . '/K/init.php';

$v = new K\Validator();
$v->email();

$r1 = $v->validate('myemail');
$r2 = $v->validate('myemail@domain.com');

$v2 = new K\Validator();
$v2->key('username')->notBlank()->minLength(6);

$r3 = $v2->validate(array(
	'username' => 'zzz',
	'password' => 'god'
));
$r4 = $v2->validate(array(
	'username' => 'my_sample_user',
	'password' => 'god'
));

var_dump($r1);
var_dump($r2);
var_dump($r3);
var_dump($r4);