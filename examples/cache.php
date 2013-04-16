<?php
define('SRC_PATH',realpath('../src'));
require SRC_PATH . '/K/init.php';
\K\DebugBar::init(array(
	'trackedObjects' => array('K\Pdo')
));
$pdo = new K\Pdo(K\Pdo::SQLITE_MEMORY);

$pdo->createTable('cache',array(
	'`key`' => 'VARCHAR',
	'value' => 'BLOB',
	'expire' => 'NUMBER'
	));

$filecache = new K\Cache(__DIR__ . '/cache');
$dbcache = new K\Cache($pdo);

$key = $filecache->get('key');
$lkey = $filecache->get('lkey');

$db_key = $dbcache->get('key');

$key++;
$lkey++;

$db_key++;

$filecache->set('key', $key);
$dbcache->set('key',$db_key); //this is always empty since it's in memory :-)
$filecache->set('lkey', $lkey,1);
//$dbcache->set('lkey','db value',0);

echo 'key : ' . $filecache->get('key');
echo '<br/>db : ' . $dbcache->get('key');
echo '<br/>lkey : ' . $filecache->get('lkey');
//echo 'db : ' . $dbcache->get('lkey');

echo '<br/>db get : ' . $dbcache->get('key') ;

echo '<pre>';
$rows = $pdo->select('cache');
print_r($rows);