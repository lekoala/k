<?php
define('SRC_PATH',realpath('../src'));
require SRC_PATH . '/K/init.php';

K\Acl::setPermissions(array(
	'read' => 1,
	'write' => 2,
	'delete' => 4
));
K\Acl::addPermission('admin');

echo "<pre>Permissions : \n";
print_r(K\Acl::getPermissions());

K\Acl::setCurrentPermissiosn(0);

K\Acl::add('write');

print_r(K\Acl::read());

//or check some random permissions

$perms = 2;

echo 'write ? ' . K\Acl::has('write',$perms);
echo 'admin ? ' . K\Acl::has('admin',$perms);

K\Acl::add('admin',$perms);
K\Acl::remove('write',$perms);

echo 'write ? ' . K\Acl::has('write',$perms);
echo 'admin ? ' . K\Acl::has('admin',$perms);