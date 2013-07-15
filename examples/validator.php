<?php

require '_bootstrap.php';

$d = [
	'name' => 'my name',
	'email' => 'ddd@ccc.com',
	'wrong_email' => 'ddd@',
	'empty' => '',
	'int' => '5',
	'phone' => '02 111 11 11',
	'nested' => [
		'name' => 'nested name',
		'email' => 'szzz@zz',
		'phone' => '0246 dssf'
	]
];

//standalone usage
$v = new k\data\Validator();

$single = [
	['validateRequired',$d['empty']],
	['validateRequired',$d['name']],
	['validatePhone',$d['phone']],
];
foreach($single as $r) {
	$method = $r[0];
	$value = $r[1];
	echo 'Validating "' . $value . '" with ' . $method . '<br/>';
	var_dump($v->$method($value));
}

//array usage
$rules = [
	'name' => [
		'required',
		'minlength' => 3
	],
	'email' => 'email',
	'wrong_email' => 'email',
	'nested[name]' => 'required',
	'nested[email]' => 'email',
	'nested[phone]' => 'phone'
];
$v = new k\data\Validator($d,$rules);
if($v->validate()) {
	echo 'valid';
}
else {
	echo '<pre>';
	print_r($v->errors());
}

//you can also throw exception
$v->validate(true);