<?php
define('SRC_PATH',realpath('../src'));
require SRC_PATH . '/K/init.php';


$coll = array(
	array('id' => 1, 'name' => 'coucou'),
	array('id' => 3, 'name' => 'brol')
);

$assoc = array(
	'my' => 'assoc',
	'array' => array(
		'demo' => 'of',
		'my' => 'array'
	),
	'simple' => array(1,4,5)
);
$simple = array('my','simple','array','demo');

echo K\Arr::get($assoc, 'array.demo'); echo '<br>';
echo K\Arr::get($simple, 1); echo '<br>';
K\Arr::set($assoc,'my','inserted'); echo '<br>';
echo '<pre>';
print_r($assoc); echo '<br>';
echo '</pre>';
K\Arr::delete($assoc,'my'); echo '<br>';
echo '<pre>';
print_r($assoc); echo '<br>';
echo '</pre>';
echo K\Arr::index($assoc, array('array','demo')); echo '<br/>';

class someClass {
	public $brol = 'test';
	protected $test;
	protected static $stat;
}
$list = 'some,items,in,a,list';


echo '<pre>';
print_r(K\Arr::make(new someClass())); echo '<br>';
echo '</pre>';
echo '<pre>';
print_r(K\Arr::make($list)); echo '<br>';
echo '</pre>';
echo '<pre>';
print_r(K\Arr::pluck($coll,'id')); echo '<br>';
echo '</pre>';
echo 'is assoc simple ? ' . K\Arr::isAssoc($simple) . '<br/>';
echo 'is assoc assoc ? ' . K\Arr::isAssoc($assoc) . '<br/>';
echo 'random : ' . K\Arr::random($simple) . '<br/>';
echo 'has my ' . K\Arr::has($simple, array('my','value')) . '<br/>';
echo 'has glub ' . K\Arr::has($simple, array('glub','value')) . '<br/>';

