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

$key = $filecache->getProperty('key');
$lkey = $filecache->getProperty('lkey');

$db_key = $dbcache->getProperty('key');

$key++;
$lkey++;

$db_key++;

$filecache->setProperty('key', $key);
$dbcache->setProperty('key',$db_key); //this is always empty since it's in memory :-)
$filecache->setProperty('lkey', $lkey,1);
//$dbcache->set('lkey','db value',0);

echo 'key : ' . $filecache->getProperty('key');
echo '<br/>db : ' . $dbcache->getProperty('key');
echo '<br/>lkey : ' . $filecache->getProperty('lkey');
//echo 'db : ' . $dbcache->get('lkey');

echo '<br/>db get : ' . $dbcache->getProperty('key') ;

echo '<pre>';
$rows = $pdo->select('cache');
print_r($rows);