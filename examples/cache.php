<?php

require '_bootstrap.php';

$pdo = new k\db\Pdo(k\db\Pdo::SQLITE_MEMORY);
$pdo->createTable('cache',array(
	'`key`' => 'VARCHAR',
	'value' => 'BLOB',
	'expire_ts' => 'NUMBER'
	));

$tb = new k\dev\Toolbar(true);
$tb->track($pdo);

$filecache = new k\cache\FileCache(__DIR__ . '/cache');
$dbcache = new k\cache\PdoCache($pdo);

$key = $filecache->get('key');
$lkey = $filecache->get('lkey');

$db_key = $dbcache->get('key',0);

$key++;
$lkey++;

$db_key++;

echo "Store $key in filecache<br/>";
$filecache->set('key', $key);
echo "Store $lkey in filecache for 10 seconds<br/>";
$filecache->set('lkey', $lkey,10); //10 seconds cache
echo "Store $db_key in dbcache<br/>";
$dbcache->set('key',$db_key); //this is always empty since it's in memory :-)

echo '<br/>file key : ' . $filecache->get('key');
echo '<br/>file lkey : ' . $filecache->get('lkey');
echo '<br/>db key : ' . $dbcache->get('key');

echo '<hr>Db table <pre>';
$rows = $pdo->select('cache');
print_r($rows);