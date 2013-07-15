<?php

require '_bootstrap.php';

//$db = new \k\db\Pdo(\k\db\Pdo::SQLITE_MEMORY);
$db = new \k\db\Pdo('mysql:root:root;host=localhost;dbname=framework');

$toolbar = new k\dev\Toolbar();
$toolbar->track($db);

//build schema
echo '<pre>';
echo $db->dropTable('user');
echo $db->dropTable('user_copy');
echo $db->dropTable('lang');
echo $db->dropTable('usertype');
echo '<hr/>';

echo $db->createTable('usertype',array('id','name'));
echo '<br/>';
echo $db->createTable('lang',array('code','name'),array('code'));
echo '<br>';
echo $db->createTable('user',array('id','username','usertype_id','password','useless'),array('username'),array('usertype_id' => 'usertype(id)'));
echo '<br/>';
echo $db->alterTable('user',array('created_at','lang_code'),array('useless'));
echo '<br/>';
echo $db->alterKeys('user', array('lang_code' => 'lang(code)'), array('usertype_id' => 'usertype(id)'));
//insert stuff
$db->insert('usertype', array(
	'name' => 'admin'
));
$db->insert('usertype', array(
	'name' => 'user'
));

$usertypes = $db->select('usertype');

$db->insert('user', array(
	'usertype_id' => 1,
	'username' => 'admin user',
	'password' => md5('my_password')
));
$db->insert('user', array(
	'usertype_id' => 2,
	'username' => 'user',
	'password' => md5('password')
));
$db->insert('lang', array(
	'code' => 'en',
	'name' => 'English'
));
//update a user
$db->update('user',array(
	'username' => 'super admin user'
),array(
	'username' => 'admin user'
));
//pass params
$db->update('user', array('username' => 'my masta user'), "username LIKE ?", array('%admin%'));

echo 'Table list<br/>';
print_r($db->listTables());

echo 'List user column<br/>';
print_r($db->listColumns('user'));

echo 'List users<br/>';
print_r($db->select('user'));

echo '</pre>';