<?php

namespace k\app;

use \InvalidArgumentException;
use \RuntimeException;

/**
 * A service controls business logic related to an entity or a group of entity
 * 
 * Basic exemple : UserService should handle logic for login, password reset etc
 */
class Service {
	
	use AppShortcuts;
	use SyntaxHelpers;

	protected $name;

	/**
	 * Define actions
	 * 
	 * @var array
	 */
	protected $actions;
	protected $tables = array();

	public function __construct($app) {
		$this->setApp($app);
		$this->init();
	}

	public function init() {
		//implement in subclass
	}

	public function getName() {
		if ($this->name === null) {
			$obj = explode('\\', get_called_class());
			$this->name = end($obj);
		}
		return $this->name;
	}

	public function getTable($name) {
		if (!isset($this->tables[$name])) {
			$this->tables[$name] = new \k\sql\Table($name, $this->getDb());
		}
		return $this->tables[$name];
	}

	public function query() {
		return \k\sql\Query::create($this->getDb());
	}
}