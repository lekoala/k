<?php

require '_bootstrap.php';

// Test how constructor and magic affect performance

class DemoRelations {
	/**
	 * @var PDO
	 */
	protected static $pdo;

	protected static $relations = [
		'Company' => [
			'primary_key' => 'id',
			'foreign_key' => 'company_id',
			'table' => 'company'
		]
	];
	protected $_relations;
	
	public static function setPdo($pdo) {
		self::$pdo = $pdo;
	}
	
	public function virtual() {
		return 'virtual ' . $this->name;
	}
	
	public function __get($name) {
		return $this->$name();
	}
	
	public function __call($name, $arguments) {
		if(property_exists($this, $name)) {
			$func = 'sprintf';
			$v = $this->$name;
			if(isset($arguments[1])) {
				$func = $arguments[1];
			}
			if($func === 'date' && !is_int($v)) {
				$v = strtotime($v);
			}
			return $func($arguments[0],$v);
		}
		if(isset(static::$relations[$name])) {
			$rel = static::$relations[$name];
			$foreign = $rel['foreign_key'];
			$sql = 'SELECT * FROM ' . $rel['table'] . ' WHERE ' . $rel['primary_key'] . ' = ' . $this->$foreign;
			$stmt = self::$pdo->query($sql);
			$stmt->setFetchMode(PDO::FETCH_CLASS,'Company');
			$res = $stmt->fetch();
			return $res;
		}
		return $name;
	}
}

class Company {
	public $id;
	public $name;
}

class Demo extends DemoRelations {

	public $id;
	public $name;
	public $company_id;
}

class DemoCombined extends DemoRelations {

	protected $id;
	protected $name;
	protected $company_id;

	public function __get($name) {
		if(property_exists($this, $name)) {
			return $this->$name;
		}
		return parent::__get($name);
	}

}

class DemoConstruct {

	public $value;

	public function __construct() {
		
	}

}

class DemoInject {

	protected $pdo;
	public $value;

	public function __construct($pdo) {
		$this->pdo = $pdo;
	}

}

class DemoMagic {

	protected $data = array();

	public function __set($name, $value) {
		$this->data[$name] = $value;
	}

}

class DemoMagicConstruct {

	protected $data = array();

	public function __construct() {
		
	}

	public function __set($name, $value) {
		$this->data[$name] = $value;
	}

}

$rounds = 1;
$count_users = 100;

//create pdo and database
$pdo = new k\db\Pdo(k\db\Pdo::SQLITE_MEMORY);
$pdo->exec('CREATE TABLE user(
	id INT,
	name VARCHAR,
	created_at DATE,
	company_id INT
)');
$pdo->exec('CREATE TABLE company(
	id INT,
	name VARCHAR
)');
DemoRelations::setPdo($pdo);

for ($i = 0; $i < $count_users; $i++) {
	$name = 'user ' . $i;
	$company_id = round(rand(1, 5));
	$pdo->exec('INSERT INTO user(id,name,company_id,created_at) VALUES(' . $i . ',\'' . $name . '\',' . $company_id . ',\''.date('Y-m-d H:i:s').'\')');
}
for ($i = 0; $i < 6; $i++) {
	$name = 'company ' . $i;
	$pdo->exec('INSERT INTO company(id,name) VALUES(' . $i . ',\'' . $name . '\')');
}

class OrmMagicConstruct extends \k\dev\Bench {

	protected $pdo;
	protected $query = 'SELECT * FROM user';

	public function __construct($pdo) {
		declare(ticks = 1);
		$this->pdo = $pdo;
	}

	private function createStatement() {
		return $this->pdo->query($this->query);
	}

	protected function DemoConstruct() {
		$res = $this->createStatement()->fetchAll(PDO::FETCH_CLASS, "DemoConstruct");
		return $res[0];
	}

	protected function DemoConstructLate() {
		$res = $this->createStatement()->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, "DemoConstruct");
		return $res[0];
	}

	protected function DemoMagic() {
		$res = $this->createStatement()->fetchAll(PDO::FETCH_CLASS, "DemoMagic");
		return $res[0];
	}

	protected function Demo() {
		$res = $this->createStatement()->fetchAll(PDO::FETCH_CLASS, "Demo");
		
		return [
			[
				'company_id' => $res[0]->company_id,
				'virtual' => $res[0]->virtual,
				'created' => $res[0]->created_at('d/m/Y','date'),
				'company_name' => $res[0]->Company()->name
			],
			$res[0],
			(array)$res[0]
		];
	}

	protected function DemoCombined() {
		$res = $this->createStatement()->fetchAll(PDO::FETCH_CLASS, "DemoCombined");
		
		return [
			[
				'company_id' => $res[0]->company_id,
				'virtual' => $res[0]->virtual,
				'created' => $res[0]->created_at('d/m/Y','date'),
				'company_name' => $res[0]->Company()->name
			],
			$res[0],
			(array)$res[0]
		];
	}

	protected function DemoInject() {
		$res = $this->createStatement()->fetchAll(PDO::FETCH_CLASS, "DemoInject", [$this->pdo]);
		return $res[0];
	}

	protected function AssocArray() {
		$res = $this->createStatement()->fetchAll(PDO::FETCH_ASSOC);
		return $res[0];
	}

}

//xdebug_start_trace('table',XDEBUG_TRACE_HTML);
//$profiler = new k\dev\Profiler();
//$profiler->start();

$bench = new OrmMagicConstruct($pdo);
$bench->run($rounds, " with $count_users users");

//echo '<pre>';
//print_r($profiler->devToolbarCallback());

//echo file_get_contents('table.xt');