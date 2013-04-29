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

$key = $filecache->getField('key');
$lkey = $filecache->getField('lkey');

$db_key = $dbcache->getField('key');

$key++;
$lkey++;

$db_key++;

$filecache->setField('key', $key);
$dbcache->setField('key',$db_key); //this is always empty since it's in memory :-)
$filecache->setField('lkey', $lkey,1);
//$dbcache->set('lkey','db value',0);

echo 'key : ' . $filecache->getField('key');
echo '<br/>db : ' . $dbcache->getField('key');
echo '<br/>lkey : ' . $filecache->getField('lkey');
//echo 'db : ' . $dbcache->get('lkey');

echo '<br/>db get : ' . $dbcache->getField('key') ;

echo '<pre>';
$rows = $pdo->select('cache');
print_r($rows);