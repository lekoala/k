<?php

namespace k;

use \InvalidArgumentException;
use \RuntimeException;

/**
 * A service controls business logic related to an entity or a group of entity
 * 
 * Basic exemple : UserService should handle logic for login, password reset etc
 * Twitter service can serve or post tweets...
 * Image service can provide thumbnails and so on...
 */
class Service {
	
	use Bridge;

	protected $name;

	/**
	 * Define actions
	 * 
	 * @var array
	 */
	protected $actions;
	protected $tables = array();

	public function getName() {
		if ($this->name === null) {
			$obj = explode('\\', get_called_class());
			$this->name = end($obj);
		}
		return $this->name;
	}

	public function getTable($name) {
		if (!isset($this->tables[$name])) {
			$this->tables[$name] = new \k\db\Table($name, $this->getDb());
		}
		return $this->tables[$name];
	}

	public function query() {
		return \k\db\Query::create($this->getDb());
	}
}