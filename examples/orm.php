<?php

require '_bootstrap.php';

$pdo = new k\db\Pdo(k\db\Pdo::SQLITE_MEMORY);
//$pdo = new \k\db\Pdo('mysql:root:root;host=localhost;dbname=framework');
$prof = new k\dev\Profiler();
//$prof->start();
$tb = new k\dev\Toolbar(true);
$tb->track($pdo);
$tb->track($prof);

class BaseModel extends k\db\Orm {

	protected static $_classPrefix = '';

}

class Lang extends BaseModel {

	public $code;
	public $name;

	public static function getPrimaryKeys() {
		return ['code'];
	}

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
	use k\db\orm\Timestamp;
use k\db\orm\Geoloc;
use k\db\orm\Address;
use k\db\orm\Info;
use k\db\orm\Lang;
use k\db\orm\Log;
use k\db\orm\Password;
use k\db\orm\Permissions;
use k\db\orm\SoftDelete;
use k\db\orm\Sortable;
use k\db\orm\Version;

	const ROLE_ADMIN = 'admininiser';
	const ROLE_USER = 'user';

	public $id;
	public $firstname;
	public $lastname;
	public $picture;
	public $birthday;
	protected static $_lang = ['translatable'];
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

// Create schema //
$pdo->foreignKeysStatus(false);
echo '<pre>';
echo Lang::syncTable();
echo '<pre>';
echo Usertype::syncTable();
echo '<br/>';
echo User::syncTable();
echo '<br/>';
echo Tag::syncTable();
echo '<br/>';
echo ProfilePic::syncTable();
echo '<hr/>';
// Configure ORM //

BaseModel::setStorage(__DIR__ . '/data');

// Basic usage //

if (Lang::count() == 0) {
	Lang::insert([
		'code' => 'fr',
		'name' => 'Français'
	]);
}
$file = new k\File(__DIR__ . '/data/pic.jpg');
$file = $file->duplicate();

$birthday = new k\Date('1985-01-16');
//$birthday = new k\Date('12:01:50');


$user = new User();
$user->firstname = 'koala';
$user->lastname = 'Portelange';
$user->fullname = 'Thomas Portelange';
$user->picture = $file;
$user->birthday = $birthday;
$user->street = 'Rue de la motte';
$user->street_no = 1;
$user->zipcode = 7061;
$user->city = 'Soignies';

$user->setTranslation('translatable', 'value', 'fr');

echo $user->get_address_location();

//$user->geocode();
$user->addInfo('test', 'val'); //you can added infos to a user that do not exist yet, because it's pendable

$id = $user->save();


echo '<hr/>Table content';
var_dump(User::getTable()->select());
echo '<hr/>';

$user2 = new User();
$user2->load($id);
$user2->firstname = 'Changed';
$user2->save();
$user2->firstname = 'Changed2';
$user2->save();

echo '<hr/>Version content';
var_dump($pdo->select('userversion'));
echo '<hr/>';

User::alterTable();

echo json_encode($user2->toArray(array('fullname')));

for ($i = 0; $i < 10; $i++) {
	$o = User::createFake(false);
	$o->deleted_at = null;
	$o->usertype_id = rand(1, 2);
	$o->save();
}

$firstuser = User::q()->fetchOne();
$firstuser->remove();

// Showing traits usage //

/*

  print_r($firstuser->getLog());

  $firstuser->permissions = 0;
  $firstuser->addPermission('comment');
  $firstuser->removePermission('comment');
  $firstuser->addPermission('post');

  echo '<pre>' . __LINE__ . "\n";
  print_r($firstuser->readPermissions());
 */

// Relations //
// has one

$usertype = new Usertype();
$usertype->name = 'client';
$usertype->save();

$type = $firstuser->Usertype();
$type->name = 'new';
$firstuser->save(); //save will cascade since we save cached objects too

echo "<pre>The new usertype has been inserted \n";
print_r($firstuser);
print_r(Usertype::select());
echo '<hr>';

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

$firstuser->addRelated(Tag::q()->fetchAll());

echo '<pre>' . __LINE__ . "\n";
print_r($firstuser->getRelated('Tag'));
echo '<hr/>';
//inject


echo 'total pics : ' . Profilepic::count() . '<br/>';


$users = User::q()->fetchAll();
echo '<table><tr><th>id</th><th>type id</th><th>type</th><th>count pics<th><th>tags</th></tr>';
foreach ($users as $user) {
	echo '<tr>';
	echo '<td>' . $user->id . '</td>';
	echo '<td>' . $user->usertype_id . '</td>';
	echo '<td>' . $user->Usertype()->name . '</td>';
	echo '<td>' . count($user->Profilepic()) . '</td>';
	echo '<td>' . count($user->tag()) . '</td>';
	echo '</tr>';
}
echo '</table>';
echo "<pre>Show enum\n";
print_r(User::enum('role'));

$dir = new k\Directory(User::getBaseFolder());
//$dir->makeEmpty();