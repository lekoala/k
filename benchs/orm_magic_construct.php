<?php

require '_bootstrap.php';

// Test how constructor and magic affect performance

class Demo {

	protected $id;
	protected $name;
	protected $company_id;
}

class DemoCombined {

	public $id;
	public $name;
	public $company_id;
	protected $_original;
	protected $_relations;

	public function __get($name) {
		return $this->$name;
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

$rounds = 5;
$count_users = 500;

//create pdo and database
$pdo = new k\db\Pdo(k\db\Pdo::SQLITE_MEMORY);
$pdo->exec('CREATE TABLE user(
	id INT,
	name VARCHAR,
	company_id INT
)');
$pdo->exec('CREATE TABLE company(
	id INT,
	name VARCHAR
)');
for ($i = 0; $i < $count_users; $i++) {
	$name = 'user ' . $i;
	$company_id = round(rand(1, 5));
	$pdo->exec('INSERT INTO user(id,name,company_id) VALUES(' . $i . ',\'' . $name . '\',' . $company_id . ')');
}
for ($i = 0; $i < 6; $i++) {
	$name = 'company ' . $i;
	$pdo->exec('INSERT INTO user(id,name) VALUES(' . $i . ',\'' . $name . '\')');
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
		if ($this->currentRound == 1) {
			var_dump($res[0]);
		}
	}

	protected function DemoConstructLate() {
		$res = $this->createStatement()->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, "DemoConstruct");
		if ($this->currentRound == 1) {
			var_dump($res[0]);
		}
	}

	protected function DemoMagic() {
		$res = $this->createStatement()->fetchAll(PDO::FETCH_CLASS, "DemoMagic");
		if ($this->currentRound == 1) {
			var_dump($res[0]);
		}
	}

	protected function Demo() {
		$res = $this->createStatement()->fetchAll(PDO::FETCH_CLASS, "Demo");
		if ($this->currentRound == 1) {
			var_dump($res[0]);
		}
	}

	protected function DemoCombined() {
		$res = $this->createStatement()->fetchAll(PDO::FETCH_CLASS, "DemoCombined");
		if ($this->currentRound == 1) {
			echo $res[0]->company_id;
			var_dump($res[0]);
		}
	}

	protected function DemoInject() {
		$res = $this->createStatement()->fetchAll(PDO::FETCH_CLASS, "DemoInject", [$this->pdo]);
	}

	protected function AssocArray() {
		$res = $this->createStatement()->fetchAll(PDO::FETCH_ASSOC);
		if ($this->currentRound == 1) {
			var_dump($res[0]);
		}
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