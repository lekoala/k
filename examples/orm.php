<?php

define('SRC_PATH', realpath('../src'));
require SRC_PATH . '/K/init.php';
\K\DebugBar::init(array(
	'trackedObjects' => array('K\Pdo','K\Log')
));


$pdo = new K\Pdo(K\Pdo::SQLITE_MEMORY);
K\Orm::configure(array(
	'pdo' => $pdo
));

/**
 * @property $id
 * @property $name
 */
class Usertype extends K\Orm {
	protected $id;
	protected $name;

}

class Tag extends K\Orm {
	protected $id;
	protected $name;
}

class Profilepic extends K\Orm {
	protected $id;
	protected $user_id;
	protected $path;
}

/**
 *  @property $id
 * 	@property $usertype_id
 * 	@property $firstname
 * 	@property $lastname
 * 	@property $password
 *  @property $created_at;
 *	@property $updated_at;
 *	@property $lat;
 *	@property $lng;
 */
class User extends K\Orm {
	
	const ROLE_ADMIN = 'admin';
	const ROLE_USER = 'user';
	
	use K\Orm\Geoloc, K\Orm\Timestamp, K\Orm\Sortable, K\Orm\Version, K\Orm\SoftDelete, K\Orm\Log, K\Orm\Meta, K\Orm\Address, K\Orm\Permissions;
	
	protected $id;
	protected $usertype_id; //or protected static $hasOne = array('Usertype');
	protected $usertype_requested_id; //or protected static $hasOne = array('Usertype' => 'requested');
	protected $firstname;
	protected $lastname;
	protected $password;
	
	protected static $manyMany = array('Tag');
	
	public function fullname() {
		return $this->firstname . ' ' . $this->lastname;
	}
	
	public static function createTable($execute = true, $foreignKeys = true) {
		$sql = parent::createTable($execute, $foreignKeys);
		$sql .= static::createTableLog($execute);
		$sql .= static::createTableMeta($execute);
		$sql .= static::createTableVersion($execute);
		return $sql;
	}
	
	public static function dropTable() {
		static::dropTableVersion();
		static::dropTableMeta();
		static::dropTableLog();
		return parent::dropTable();
	}
	
	public function onPreSave() {
		$this->onPreSaveTimestamp();
		$this->onPreSaveVersion();
	}
}

echo '<pre>';
Usertype::createTable();
User::createTable();
Tag::createTable();
ProfilePic::createTable();

$user = new User();
$user->firstname = 'Thomas';
$user->lastname = 'Portelange';
$id = $user->save();

//var_dump(User::select());

$user2 = new User($id);
$user2->firstname = 'Changed';
$user2->save();

User::alterTable();

echo json_encode($user2->toArray(array('fullname')));

for($i = 0; $i < 10; $i++) {
	$o = User::createFake(false);
	$o->deleted_at = null;
	$o->save();
}

$firstuser = User::get()->fetchOne();
$firstuser->remove();

$firstuser = User::get()->fetchOne(); //don't get the same because it has been soft removed

$firstuser->addMeta('key','value');
$firstuser->addMeta('key','value2');

print_r($firstuser->getMeta('key'));

$firstuser->removeMeta('key');

print_r($firstuser->getMeta());

$firstuser->log('meta updated');

print_r($firstuser->getLog());

$firstuser->permissions = 0;
$firstuser->addPermission('comment');
$firstuser->removePermission('comment');
$firstuser->addPermission('post');

echo '<pre>' . __LINE__ . "\n";
print_r($firstuser->readPermissions());

// has one

$usertype = new Usertype();
$usertype->name = 'client';
$usertype->save();

$firstuser->usertype = new Usertype();
//$firstuser->usertype->name = 'Test';
$firstuser->save(); //save will cascade

echo '<pre>' . __LINE__ . "\n";
print_r($firstuser);
print_r(Usertype::select());

// has many

foreach(range(1,5) as $i) {
	$o = ProfilePic::createFake(false);
	$o->save();
	$firstuser->addRelated($o);
}
$firstuser->removeRelated($o);
print_r($firstuser->profilepic());

// many many

$tag = new Tag();
$tag->name = 'Test';
$tag->save();

$firstuser->addRelated($tag);

echo 'user ' . $firstuser->getId . ' has tag ' . $tag->getId() . '<br/>';

echo '<pre>' . __LINE__ . "\n";
print_r($firstuser->getRelated('Tag'));

foreach(range(1,5) as $i) {
	Tag::createFake();
}

$firstuser->addRelated(Tag::get()->fetchAll());

echo '<pre>' . __LINE__ . "\n";
print_r($firstuser->getRelated('Tag'));
echo '<hr/>';
//inject


echo 'total pics : ' . Profilepic::count() . '<br/>';


$users = User::get()->fetchAll();
Usertype::inject($users);
echo 'inject profile pic <br/>';
Profilepic::inject($users);
echo 'inject tag<br/>';
Tag::inject($users);
echo '<hr/>';
foreach($users as $user) {
	echo 'user id ' . $user->id . '<br/>';
	echo 'usertype : ' . $user->usertype->name . '<br/>';
	echo 'profile pics : ' . count($user->profilepic()) . '<br/>';
	echo 'tags : ' . count($user->tag()) . '<br/>';
	echo '<hr/>';
}

echo '<pre>' . __LINE__ . "\n";
print_r(User::enum('role'));