<?php

namespace db;

use \PDO;
use \PDOException as NativePdoException;

/**
 * Description of config
 *
 * @author tportelange
 */
class Db {

	protected $pdo = null;
	protected $user;
	protected $password;
	protected $dsn;
	protected $dbtype;
	protected $dbname;
	protected $options;
	protected $log = array();
	protected $logger;

	public function __construct($dsn, $user = null, $password = null, array $options = array()) {
		if (is_array($dsn)) {
			//extract params
			extract($dsn);
			$params = array('user', 'password', 'options', 'dbtype', 'driver', 'database', 'username');
			foreach ($params as $param) {
				if (isset($dsn[$param])) {
					$$param = $dsn[$param];
					unset($dsn[$param]);
				}
			}

			//alias
			if (isset($database)) {
				$dbname = $database;
				$dsn['dbname'] = $database;
			}
			if (isset($driver)) {
				$dbtype = $driver;
			}
			if (isset($username)) {
				$user = $username;
			}

			//default host
			if (isset($dbtype) && in_array($dbtype, array('mysql'))) {
				if (!isset($dsn['host'])) {
					$dsn['host'] = 'localhost';
				}
			}

			//flatten array, except for sqlite which pass just a string without keys
			foreach ($dsn as $k => $v) {
				if (!is_int($k)) {
					$dsn[$k] = $k . '=' . $v;
				}
			}

			//{dbtype}:dbname={dbname};host={host};port={port}
			$dsn = $dbtype . ':' . implode(';', array_values($dsn));
		} else {
			$params = self::parseDsn($dsn);
			extract($params);
		}

		$this->dsn = $dsn;
		$this->dbtype = $dbtype;
		if (isset($dbname)) {
			$this->dbname = $dbname;
		}
		$this->user = $user;
		$this->password = $password;
		$this->options = $options;
	}

	/**
	 * Parse a dsn like {dbtype}:dbname={dbname};host={dbhost};port={dbport}
	 * 
	 * Here are the valid parameters per dbtype
	 * mysql for MySQL (host, port, dbname, unix_socket)
	 * pgsql for Postgres (host, port, dbname,user, password)
	 * sqlite for SQLite (dbname, could be a file path or :memory:)
	 * 
	 * @param string|array $dsn
	 * @return array
	 */
	protected static function parseDsn($dsn) {
		if (is_array($dsn)) {
			return $dsn;
		}

		//extract dbtype
		$dbtypeDelimiter = strpos($dsn, ':');
		$paramsDelimiter = strpos($dsn, ';');
		$dbtype = substr($dsn, 0, $dbtypeDelimiter);
		$dbname = substr($dsn, $dbtypeDelimiter + 1, strlen($dsn));

		//stop there for sqlite
		if ($dbtype === 'sqlite') {
			//dbname could be a file path or :memory:
			return compact('driver', 'dbname');
		}

		//keep parsing dbname to extract params
		preg_match_all('/([a-zA-Z0-9]+)=([a-zA-Z0-9_]+)/', $dsn, $matches);
		$params = compact('dbtype');
		$matches = array_combine($matches[1], $matches[2]);
		foreach ($matches as $k => $v) {
			$params[$k] = $v;
		}

		//parse username:password@host
		$url = substr($dsn, $dbtypeDelimiter + 1, $paramsDelimiter - $dbtypeDelimiter - 1);
		$hostSeparator = strrpos($url, '@');
		$passwordSeparator = strpos($url, ':');
		$end = strlen($url);
		if ($hostSeparator !== false) {
			$host = substr($url, $hostSeparator + 1, $end);
			$portSeparator = strpos($host, ':');
			if ($portSeparator !== false) {
				$params['port'] = substr($host, $portSeparator + 1, strlen($host));
				$host = substr($host, 0, $portSeparator);
			}
			$params['host'] = $host;
			$params['user'] = substr($url, 0, $hostSeparator);
			$end = $hostSeparator;
		}
		if ($passwordSeparator !== false) {
			$params['user'] = substr($url, 0, $passwordSeparator);
			$params['password'] = substr($url, $passwordSeparator + 1, $end - $passwordSeparator - 1);
		}

		return $params;
	}

	public function getPdo() {
		if (!$this->pdo) {
			$this->setPdo($this->dsn, $this->user, $this->password, $this->options);
		}
		return $this->pdo;
	}

	public function setPdo($dsn, $user = null, $password = null, array $options = array()) {
		$this->pdo = new PDO($dsn, $user, $password, $options);

		//always throw exception
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);

		//don't emulate prepare
		//WARNING : somehow this messes up transactions...
//		$this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

		//use custom pdo statement class
		$this->pdo->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('db\statement', array($this)));
	}
	
	public function setLogger($logger) {
		$this->logger = $logger;
	}
	
	/**
	 * Exec wrapper for stats
	 * 
	 * @param string $statement
	 * @return int
	 */
	public function exec($statement) {
		try {
			$time = microtime(true);
			$result = $this->getPdo()->exec($statement);
			$time = microtime(true) - $time;
			$this->log($statement, $time);
		} catch (NativePdoException $e) {
			$this->log($statement);
			throw new PdoException($e);
		}
		return $result;
	}

	/**
	 * Query wrapper for stats
	 * 
	 * @param string $statement
	 * @return db\statement
	 */
	public function query($statement) {
		try {
			$time = microtime(true);
			$result = $this->getPdo()->query($statement);
			$time = microtime(true) - $time;
			$this->log($statement, $time);
		} catch (NativePdoException $e) {
			$this->log($statement);
			throw new PdoException($e);
		}

		return $result;
	}

	/**
	 * Prepare wrapper
	 * 
	 * @param string $statement
	 * @param array $driver_options
	 * @return db\statement
	 */
	public function prepare($statement, $driver_options = array()) {
		try {
			return $this->getPdo()->prepare($statement, $driver_options)->setDb($this);
		} catch (NativePdoException $e) {
			$this->log($statement);
			throw new PdoException($e);
		}
	}

	/**
	 * Log queries
	 * 
	 * @param string $sql
	 * @param int $time
	 */
	public function log($sql, $time = null) {
		$this->log[] = compact('sql', 'time');
		if($this->logger) {
			$this->logger->debug($sql);
		}
	}

	public function transaction_start_no_commit() {
		return $this->query('SET AUTOCOMMIT=0; START TRANSACTION');
	}
	
	public function transaction_start() {
		return $this->query('START TRANSACTION');
	}

	public function transaction_rollback() {
		return $this->query('ROLLBACK');
	}

	public function transaction_commit() {
		return $this->query('COMMIT');
	}

	public function getLog() {
		return $this->log;
	}

	/**
	 * More advanced quote (quote arrays, return NULL properly, quotes INT properly...)
	 * 
	 * @param string $value
	 * @param int $parameter_type
	 * @return string 
	 */
	public function quote($value, $parameter_type = null) {
		if (is_array($value)) {
			$value = implode(',', array_map(array($this, 'quote'), $value));
			return $value;
		} elseif (is_null($value)) {
			return "NULL";
		} elseif (($value !== true) && ((string) (int) $value) === ((string) $value)) {
			//otherwise int will be quoted, also see @https://bugs.php.net/bug.php?id=44639
			return $this->getPdo()->quote(intval($value), PDO::PARAM_INT);
		}
		$parameter_type = PDO::PARAM_STR;
		return $this->getPdo()->quote($value, $parameter_type);
	}

	public function q($from = null) {
		return db\query::create($from)->setDb($this);
	}

	function lastInsertId() {
		return $this->getPdo()->lastInsertId();
	}

	/**
	 * Insert records
	 * 
	 * @param string $table
	 * @param array $data
	 * @return int The id of the record
	 */
	function insert($table, array $data) {
		$params = array();
		foreach ($data as $k => $v) {
			$keys[] = $k;
			$values[] = ':' . $k;
			$params[':' . $k] = $v;
		}

		$sql = "INSERT INTO " . $table . " (" . implode(",", $keys) . ") VALUES (" . implode(',', $values) . ")";

		$stmt = $this->prepare($sql);
		$result = $stmt->execute($params);
		$stmt->closeCursor();
		$stmt = null;
		if ($result) {
			return $this->lastInsertId();
		}
		return $result;
	}

	/**
	 * Update records
	 * 
	 * @param string $table
	 * @param array $data
	 * @param array|string $where
	 * @param array $params
	 * @return bool
	 */
	function update($table, array $data, $where = null, $params = array()) {
		$sql = 'UPDATE ' . $table . " SET \n";
		self::toNamedParams($where, $params);
		foreach ($data as $k => $v) {
			$placeholder = ':' . $k;
			while(isset($params[$placeholder])) {
				$placeholder .= rand(1,9);
			}
			$sql .= $k . ' = ' . $placeholder . ', ';
			$params[$placeholder] = $v;
		}
		$sql = rtrim($sql, ', ');
		$this->injectWhere($sql, $where, $params);
		$stmt = $this->prepare($sql);
		$result = $stmt->execute($params);
		$stmt->closeCursor();
		$stmt = null;
		return $result;
	}

	/**
	 * Delete records
	 * 
	 * @param string $table
	 * @param array|string $where
	 * @param array $params
	 * @return bool
	 */
	function delete($table, $where = null, $params = array()) {
		$sql = 'DELETE FROM ' . $table . '';
		$this->injectWhere($sql, $where, $params);
		$stmt = $this->prepare($sql);
		$result = $stmt->execute($params);
		$stmt->closeCursor();
		$stmt = null;
		return $result;
	}
	
	/**
	 * A fix to convert ? to named params
	 * 
	 * @param string $where
	 * @param array $params 
	 */
	protected static function toNamedParams(&$where, array &$params) {
		if (is_string($where) && preg_match('/\?/', $where, $matches)) {
			$count = count($matches);
			$namedParams = array();
			for ($i = 0; $i < $count; $i++) {
				$where = preg_replace('/\?/', ':placeholder' . $i, $where, 1);
				$namedParams[':placeholder' . $i] = $params[$i];
			}
			$params = $namedParams;
		}
	}

		/**
	 * Inject where clause at the end of a sql statement
	 * 
	 * @param string $sql
	 * @param string|array $where
	 * @return string
	 */
	protected function injectWhere(&$sql, &$where, &$params) {
		if (is_array($where)) {
			$pdo = $this;
			array_walk($where, function (&$item, $key) use (&$params, $pdo) {
						$placeholder = ':' . $key;
						while(isset($params[$placeholder])) {
							$placeholder .= rand(1,9);
						}
							
						if (is_array($item)) {
							$item = array_unique($item);
							$item = $key . " IN (" . $pdo->quote($item) . ")";
						} elseif (is_string($key)) {
							$params[$placeholder] = $item;
							$item = $key . " = " . $placeholder;
						} else {
							$item = $item . " = " . $placeholder;
						}
					});
			$where = implode(' AND ', $where);
		}
		if (!empty($where)) {
			$sql .= ' WHERE ' . $where;
		}
		return $sql;
	}
	
	/**
	 * Fetch a single value
	 * 
	 * @param string $field
	 * @param string $from
	 * @return db\query
	 */
	public function fetchValue($field, $from = null) {
		return $this->q($from)->fields($field)->get('fetchValue');
	}

}