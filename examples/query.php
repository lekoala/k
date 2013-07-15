<?php

require '_bootstrap.php';

$pdo = new k\db\Pdo(k\db\Pdo::SQLITE_MEMORY);

$tb = new k\dev\Toolbar(true);
$tb->track($pdo);

//prepare data
$pdo->createTable('user',array('id','usergroup_id', 'name'));
$pdo->createTable('usergroup',array('id','name'));

$pdo->insert('usergroup',array('name' => 'my group'));
$pdo->insert('user', array('usergroup_id' => 1, 'name' => "User in a group"));
$pdo->insert('user', array('name' => "User not in a group"));

$query = new k\db\Query($pdo);
$query->from('user')->where('id',1);

echo '<pre>';
echo 'Fetch all<br/>';
print_r($query->fetchAll());
echo 'Fetch one<br/>';
print_r($query->fetchOne());

$query->where(); //remove where clause

echo 'Fetch map<br/>';
print_r($query->fetchMap());

$q2 = new k\db\Query($pdo);
$q2->from('user')->innerJoin('usergroup','usergroup.id = user.usergroup_id')->addField('usergroup.name as usergroup');
echo 'Fetch all<br/>';
print_r($q2->fetchAll());




