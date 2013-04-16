<?php
define('SRC_PATH',realpath('../src'));
require SRC_PATH . '/K/init.php';

$pdo = new K\Pdo(K\Pdo::SQLITE_MEMORY);
$pdo->createTable('log',array('id','created_at','level','message'));

K\Log::setPdo($pdo);

K\DebugBar::init();
K\DebugBar::track('K\Log');
K\Log::debug('Debug message');
K\Log::info($pdo);

K\Log::critical('This will be sent by email');

$rows = $pdo->select('log');

echo '<pre>' . __LINE__ . "\n";
print_r($rows);
exit();