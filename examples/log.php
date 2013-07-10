<?php

require '_bootstrap.php';


$pdo = new k\db\Pdo(k\db\Pdo::SQLITE_MEMORY);
$pdo->createTable('log', array('id', 'created_at', 'level', 'message'));

$tb = new k\dev\Toolbar(true);
$tb->track($pdo);

$filelog = new k\log\FileLogger(__DIR__ . '/data/log');
$dblog = new k\log\PdoLogger($pdo);
echo 'Log an alert on filelog<br/>';
var_dump($filelog->alert('Test alert'));
echo 'Log a debug on dblog<br/>';
var_dump($dblog->debug('Test debug on db'));

$rows = $pdo->select('log');

echo '<pre>' . __LINE__ . "\n";
print_r($rows);
exit();