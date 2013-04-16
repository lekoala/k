<?php

namespace k\log;

/**
 * PdoLogger
 * 
 * Log items in a database
 *
 * @author lekoala
 */
class PdoLogger extends LoggerAbstract {

	/**
	 * @var PDO
	 */
	protected $pdo;
	protected $table = 'log';
	protected $fields = array('created_at', 'level', 'message');

	public function __construct($pdo) {
		$this->setPdo($pdo);
	}

	public function getPdo() {
		return $this->pdo;
	}

	public function setPdo(PDO $pdo) {
		$this->pdo = $pdo;
		return $this;
	}

	public function getTable() {
		return $this->table;
	}

	public function setTable($table) {
		$this->table = $table;
		return $this;
	}

	public function getFields() {
		return $this->fields;
	}

	public function setFields(array $fields) {
		$this->fields = $fields;
		return $this;
	}

	public function getSqlCreate() {
		return "CREATE TABLE {$this->table} (
			id INTEGER NOT NULL,
			created_at DATETIME NOT NULL,
			level VARCHAR(255) NOT NULL,
			message TEXT NOT NULL
			PRIMARY KEY (id)
);";
	}

	protected function _log($level, $message, $context = array()) {
		$created_at = date('Y-m-d H:i:s');
		$vars = get_defined_vars();

		$keys = $params = $values = array();
		foreach ($this->fields as $f) {
			$keys[] = $f;
			$params[] = ':' . $f;

			if (isset($vars[$f])) {
				$v = $vars[$f];
			} else if (isset($context[$f])) {
				$v = $context[$f];
			} else {
				$v = null;
			}

			$values[$f] = $v;
		}

		$stmt = $this->getPdo()->prepare("INSERT INTO " . implode(',', $keys) . " VALUES (" . implode(',', $params) . ")");
		$stmt->execute($values);
	}

	public function read($date, $lines = 5, $reverse = false) {
		
	}

}