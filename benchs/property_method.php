<?php

require '_bootstrap.php';

// Test property_exist and method_exist 

/**
 * @property $prop
 */
class Prop {

	protected $prop = 'prop';
	public $pub = 'pub';

	public function __get($name) {
		if (property_exists($this, $name)) {
			return $this->$name;
		}
	}

}

/**
 * @property $method
 */
class Method {

	public function pub() {
		return 'pub';
	}

	protected function get_method() {
		return 'method';
	}

	public function __get($name) {
		$name = 'get_' . $name;
		if (method_exists($this, $name)) {
			return $this->$name();
		}
	}

}

class Both {

	protected $prop = 'protprop';
	public $pub = 'pubprop';

	public function pub() {
		return 'pubmethod';
	}

	protected function get_method() {
		return 'protmethod';
	}

	public function __get($name) {
		if (property_exists($this, $name)) {
			return $this->$name;
		}
		$name = 'get_' . $name;
		if (method_exists($this, $name)) {
			return $this->$name();
		}
	}

}

class BothR {

	protected $prop = 'protprop';
	public $pub = 'pubprop';

	public function pub() {
		return 'pubmethod';
	}

	protected function get_method() {
		return 'protmethod';
	}

	public function __get($name) {
		$method = 'get_' . $name;
		if (method_exists($this, $method)) {
			return $this->$method();
		}
		if (property_exists($this, $name)) {
			return $this->$name;
		}
	}

}

class MethodProperty extends \k\dev\Bench {

	protected $prop;
	protected $method;
	protected $both;
	protected $bothr;

	public function __construct() {
		$this->prop = new Prop();
		$this->method = new Method();
		$this->both = new Both;
		$this->bothr = new BothR;
	}

	protected function PublicProp() {
		return $this->prop->pub;
	}

	protected function ProtectedProp() {
		return $this->prop->prop;
	}

	protected function PublicMethod() {
		return $this->method->pub();
	}

	protected function ProtectedMethod() {
		return $this->method->method;
	}
	
	protected function Both() {
		return [$this->both->pub,$this->both->method,$this->both->pub(),$this->both->prop];
	}
	
	protected function BothR() {
		return [$this->bothr->pub,$this->bothr->method,$this->bothr->pub(),$this->bothr->prop];
	}

}

$rounds = 1000;
$bench = new MethodProperty();
$bench->run($rounds);

//echo '<pre>';
//print_r($profiler->devToolbarCallback());

//echo file_get_contents('table.xt');