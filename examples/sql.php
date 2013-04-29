<?php

define('SRC_PATH', realpath('../src'));
require SRC_PATH . '/K/init.php';
\K\DebugBar::init(array(
	'trackedObjects' => array('K\Pdo','K\Log')
));


$pdo = new K\Pdo(K\Pdo::SQLITE_MEMORY);
\K\SqlQuery::configure(array('pdo' => $pdo));

$pdo->createTable('user',array('id','usergroup_id', 'name'));
$pdo->createTable('usergroup',array('id','name'));

$pdo->insert('usergroup',array('name' => 'my group'));
$pdo->insert('user', array('usergroup_id' => 1, 'name' => "User in a group"));
$pdo->insert('user', array('name' => "User not in a group"));

$query = new \K\SqlQuery();
$query->from('user')->where('id',1);

echo '<pre>' . __LINE__ . "\n";
print_r($query->fetchAll());
exit();