<?php
define('SRC_PATH',realpath('../src'));
require SRC_PATH . '/K/init.php';
\K\DebugBar::init(array(
	'trackedObjects' => array('K\Pdo')
));

$db = new \K\Pdo(\K\Pdo::SQLITE_MEMORY);
//build schema
echo '<pre>';
echo $db->createTable('usertype',array('id','name'));
echo '<br/>';
echo $db->createTable('lang',array('code','name'),array(),array('code'));
echo '<br>';
echo $db->createTable('user',array('username','usertype_id','name','password','useless'),array('usertype_id' => 'usertype(id)'),array('username'));
echo '<br/>';
echo $db->alterTable('user',array('created_at','lang_code'),array('useless'));
echo '<br/>';
echo $db->addForeignKeys('user', array('lang_code' => 'lang(code)'));
//insert stuff
$db->insert('usertype', array(
	'name' => 'admin'
));
$db->insert('usertype', array(
	'name' => 'user'
));
$db->insert('user', array(
	'usertype_id' => 1,
	'name' => 'admin user',
	'password' => md5('my_password')
));
$db->insert('user', array(
	'usertype_id' => 2,
	'name' => 'user',
	'password' => md5('password')
));
$db->insert('lang', array(
	'code' => 'en',
	'name' => 'English'
));
//update a user
$db->update('user',array(
	'name' => 'super admin user'
),array(
	'name' => 'admin user'
));
//pass params
$db->update('user', array('name' => 'my masta user'), "name LIKE ?", array('%admin%'));

//$db->createTableLike('user');

echo '</pre>';