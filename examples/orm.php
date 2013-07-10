<?php

require '_bootstrap.php';

$pdo = new k\db\Pdo(k\db\Pdo::SQLITE_MEMORY);

$tb = new k\dev\Toolbar(true);
$tb->track($pdo);

class BaseModel extends k\db\Orm {

	protected static $_classPrefix = '';
}

class Usertype extends BaseModel {

	public $id;
	public $name;

}

class Tag extends BaseModel {

	public $id;
	public $name;

}

class Profilepic extends BaseModel {

	public $id;
	public $path;
	protected static $_hasOne = ['User'];

}
class User extends BaseModel {

	const ROLE_ADMIN = 'admin';
	const ROLE_USER = 'user';

	public $id;
	public $firstname;
	public $lastname;
	public $picture;
	public $password;
	public $birthday;
	protected static $_hasOne = ['Usertype', 'Requestedtype' => 'Usertype', 'Profilepic'];

	public function get_fullname() {
		return $this->firstname . ' ' . $this->lastname;
	}

	//setter
	public function set_fullname($value) {
		$parts = explode(' ', $value);
		$this->firstname = array_shift($parts);
		$this->lastname = implode(' ', $parts);
	}

}
echo '<pre>';
echo Usertype::syncTable();
echo '<br/>';
echo User::syncTable();
echo '<br/>';
echo Tag::syncTable();
echo '<br/>';
echo ProfilePic::syncTable();
echo '<hr/>';
BaseModel::setStorage(__DIR__ . '/data');

$file = new k\File(__DIR__ . '/data/pic.jpg');
$file = $file->duplicate();

$birthday = new k\Date('1985-01-16');

$user = new User();
$user->firstname = 'koala';
$user->lastname = 'Portelange';
$user->fullname = 'Thomas Portelange';
$user->picture = $file;
$user->birthday = $birthday;
$id = $user->save();

//var_dump(User::select());

$user2 = new User($id);
$user2->firstname = 'Changed';
$user2->save();

User::alterTable();

echo json_encode($user2->toArray(array('fullname')));

for ($i = 0; $i < 10; $i++) {
	$o = User::createFake(false);
	$o->deleted_at = null;
	$o->save();
}

$firstuser = User::get()->fetchOne();
$firstuser->remove();

$firstuser = User::get()->fetchOne(); //don't get the same because it has been soft removed

$firstuser->addMeta('key', 'value');
$firstuser->addMeta('key', 'value2');

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

foreach (range(1, 5) as $i) {
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

foreach (range(1, 5) as $i) {
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
foreach ($users as $user) {
	echo 'user id ' . $user->id . '<br/>';
	echo 'usertype : ' . $user->usertype->name . '<br/>';
	echo 'profile pics : ' . count($user->profilepic()) . '<br/>';
	echo 'tags : ' . count($user->tag()) . '<br/>';
	echo '<hr/>';
}

echo '<pre>' . __LINE__ . "\n";
print_r(User::enum('role'));

K\File::emptyDir(User::getBaseFolder());