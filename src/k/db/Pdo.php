<?php

namespace k\db;

use \PDO as NativePdo;
use \PDOException as NativePdoException;
use \RuntimeException;
use \Exception;

/**
 * Pdo extension. The class extends PDO to allow itself to pass
 * as an instance of PDO.
 * 
 * Main functionnalities:
 * - Allow to pass an array of arguments to the constructor
 * - Lazy connection
 * - Throws exception by default
 * - You can attach a logger or use the built in log functionnality
 * - Sql helpers
 * - Sql formatter and highlight
 * - Factories for Query and Table
 * - Connections registry
 * 
 * @link https://github.com/troelskn/pdoext/blob/master/lib/pdoext/connection.inc.php
 * @author lekoala
 */
class Pdo extends NativePdo {

	const SQLITE_MEMORY = 'sqlite::memory:';
	const KEY_PRIMARY = 'primary';
	const KEY_FOREIGN = 'foreign';
	const TYPE_SQLITE = 'sqlite';
	const TYPE_MYSQL = 'mysql';
	const TYPE_PGSQL = 'pgsql';
	const TYPE_MSSQL = 'mssql';
	const REL_TABLE = 'table';
	const REL_COLUMN = 'column';
	const REL_REFERENCED_TABLE = 'referenced_table';
	const REL_REFERENCED_COLUMN = 'referenced_column';
	const META_PK = 'pk';
	const META_TYPE = 'type';
	const META_DEFAULT = 'default';
	const META_BLOB = 'blob';

	/**
	 * Store instances
	 * @var array
	 */
	protected static $instances = array();

	/**
	 * Inner pdo instance to allow lazy connection
	 * @var NativePdo
	 */
	protected $pdo = null;

	/**
	 * Friendly name for the connection
	 * @var string
	 */
	protected $name;

	/**
	 * User
	 * @var string
	 */
	protected $user;

	/**
	 * Password
	 * @var password
	 */
	protected $password;

	/**
	 * Dsn built from given arguments or as is
	 * @var string
	 */
	protected $dsn;

	/**
	 * Dbtype (mysql, sqlite...)
	 * @var string
	 */
	protected $dbtype;

	/**
	 * Db name if specified
	 * @var string
	 */
	protected $dbname;

	/**
	 * Options for the connection
	 * @var options
	 */
	protected $options;

	/**
	 * Store queries
	 * @var array
	 */
	protected $log = array();

	/**
	 * Log level to use for the logger
	 * @var string
	 */
	protected $logLevel = 'debug';

	/**
	 * Define a psr-3 logger
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $logger;

	/**
	 * Cache table resolution
	 * @var array
	 */
	protected $tables = array();

	/**
	 * Create a new instance of the pdo. The connection is actually made later.
	 * 
	 * @param string|array $dsn You can overload this argument with an array of parameters
	 * @param string $user
	 * @param string $password
	 * @param array $options
	 */
	public function __construct($dsn, $user = null, $password = null, array $options = array()) {
		if (is_array($dsn)) {
			//extract params
			extract($dsn);
			$params = array('name', 'user', 'password', 'options', 'dbtype', 'driver', 'database', 'username');
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

			if (isset($name)) {
				$this->setName($name);
			}

			//{dbtype}:dbname={dbname};host={host};port={port}
			$dsn = $dbtype . ':' . implode(';', array_values($dsn));
		} else {
			$params = self::parseDsn($dsn);
			extract($params);
		}

		$this->setDsn($dsn);
		$this->setDbtype($dbtype);
		if (isset($dbname)) {
			$this->setDbname($dbname);
		}
		$this->setUser($user);
		$this->setPassword($password);
		$this->setOptions($options);

		//store in registry
		if (empty(self::$instances)) {
			self::$instances['default'] = $this;
		}
		self::$instances[$this->getName()] = $this;
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
			return compact('dbtype', 'dbname');
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

	/**
	 * Get inner pdo instance
	 * 
	 * @return \PDO
	 */
	public function getPdo() {
		if (!$this->pdo) {
			$this->setPdo($this->dsn, $this->user, $this->password, $this->options);
		}
		return $this->pdo;
	}

	/**
	 * Set inner pdo instance
	 * 
	 * @param string $dsn
	 * @param string $user
	 * @param string $password
	 * @param array $options
	 */
	public function setPdo($dsn, $user = null, $password = null, array $options = array()) {
		$this->setDsn($dsn);
		$this->setUser($user);
		$this->setPassword($password);
		$this->setOptions($options);

		$this->pdo = new NativePdo($dsn, $user, $password, $options);

		//always throw exception
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		//use custom pdo statement class
		$this->pdo->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('\k\db\PdoStatement', array($this)));
	}

	/**
	 * Get a connection
	 * 
	 * @param string $name
	 * @return \k\db\Pdo
	 */
	public static function get($name = 'default') {
		if (!isset(self::$instances[$name])) {
			throw new RuntimeException('Connection ' . $name . ' does not exist');
		}
		return self::$instances[$name];
	}

	/**
	 * Get friendly name
	 * 
	 * @return string
	 */
	public function getName() {
		if (!$this->name) {
			$dbname = $this->getDbname();
			if ($dbname && $this->getDbtype() == 'sqlite') {
				$dbname = basename($dbname);
			}
			if ($dbname) {
				$this->name = $dbname;
			} else {
				$this->name = 'connection_' . count(self::$instances);
			}
		}
		return $this->name;
	}

	public function setName($name) {
		$this->name = $name;
		return $this;
	}

	public function getUser() {
		return $this->user;
	}

	public function setUser($user) {
		$this->user = $user;
		return $this;
	}

	public function getPassword() {
		return $this->password;
	}

	public function setPassword($password) {
		$this->password = $password;
		return $this;
	}

	public function getDsn() {
		return $this->dsn;
	}

	public function setDsn($dsn) {
		$this->dsn = $dsn;
		return $this;
	}

	public function getDbtype() {
		return $this->dbtype;
	}

	public function setDbtype($dbtype) {
		$this->dbtype = $dbtype;
		return $this;
	}

	public function getDbname() {
		return $this->dbname;
	}

	public function setDbname($dbname) {
		$this->dbname = $dbname;
		return $this;
	}

	public function getOptions() {
		return $this->options;
	}

	public function setOptions($options) {
		$this->options = $options;
		return $this;
	}

	public function getLog() {
		return $this->log;
	}

	public function setLog(array $log) {
		return $this->log = $log;
	}

	public function getLogLevel() {
		return $this->logLevel;
	}

	public function setLogLevel($logLevel) {
		$this->logLevel = $logLevel;
		return $this;
	}

	/**
	 * @return \Psr\Log\LoggerInterface
	 */
	public function getLogger() {
		return $this->logger;
	}

	public function setLogger($logger) {
		$this->logger = $logger;
		return $this;
	}

	/**
	 * Exec wrapper
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
			throw new PdoException($e, $this);
		}
		return $result;
	}

	/**
	 * Query wrapper
	 * 
	 * @param string $statement
	 * @return \k\db\PdoStatement
	 */
	public function query($statement) {
		try {
			$time = microtime(true);
			$result = $this->getPdo()->query($statement);
			$time = microtime(true) - $time;
			$this->log($statement, $time);
		} catch (NativePdoException $e) {
			$this->log($statement);
			throw new PdoException($e, $this);
		}

		return $result;
	}

	/**
	 * Prepare wrapper
	 * 
	 * @param string $statement
	 * @param array $driver_options
	 * @return \k\db\PdoStatement
	 */
	public function prepare($statement, $driver_options = array()) {
		try {
			return $this->getPdo()->prepare($statement, $driver_options);
		} catch (NativePdoException $e) {
			$this->log($statement);
			throw new PdoException($e, $this);
		}
	}

	/**
	 * Log queries
	 * 
	 * @param string $sql
	 * @param int $time
	 */
	public function log($sql, $time = 0) {
		$this->log[] = compact('sql', 'time');
		if ($this->logger) {
			$this->logger->log($this->getLogLevel(), $sql);
		}
	}

	/**
	 * Get last query
	 *
	 * @return string
	 */
	public function getLastQuery() {
		if (!empty($this->log)) {
			$last = end($this->log);
			return $last['sql'];
		}
	}

	/**
	 * Start transaction
	 * 
	 * @param bool $autocommit
	 * @return int
	 */
	public function transactionStart($autocommit = false) {
		if ($autocommit) {
			return $this->exec('SET AUTOCOMMIT=0; START TRANSACTION');
		}
		return $this->exec('START TRANSACTION');
	}

	/**
	 * Rollback transaction
	 * 
	 * @return int
	 */
	public function transactionRollback() {
		return $this->exec('ROLLBACK');
	}

	/**
	 * Commit transaction
	 * 
	 * @return init
	 */
	public function transactionCommit() {
		return $this->exec('COMMIT');
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
			return (int) $value;
		}
		$parameter_type = PDO::PARAM_STR;
		return $this->getPdo()->quote($value, $parameter_type);
	}

	/**
	 * Get last inserted id performed by the current connection (even if rolled back)
	 * 
	 * @param string $seqname
	 * @return int
	 */
	public function lastInsertId($name = null) {
		return $this->getPdo()->lastInsertId($name);
	}

	/* sql builders */

	/**
	 * Select
	 * 
	 * @param string $table
	 * @param array|string $where
	 * @param array|string $orderBy
	 * @param array|string $limit
	 * @param array|string $fields
	 * @param array $params
	 * @return _pdo_statement 
	 */
	public function select($table, $where = null, $orderBy = null, $limit = null, $fields = '*', $params = array()) {
		if (is_array($fields)) {
			$fields = implode(',', $fields);
		}
		$sql = 'SELECT ' . $fields . ' FROM ' . $table . '';
		$this->injectWhere($sql, $where, $params);
		if (!empty($orderBy)) {
			if (is_array($orderBy)) {
				$orderBy = implode(',', $orderBy);
			}
			$sql .= ' ORDER BY ' . $orderBy;
		}
		if (!empty($limit)) {
			if (is_array($limit)) {
				$limit = implode(',', $limit);
			}
			$sql .= ' LIMIT ' . $limit;
		}
		$stmt = $this->prepare($sql);
		$stmt->execute($params);
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$stmt->closeCursor();
		$stmt = null;
		return $results;
	}

	/**
	 * Insert records
	 * 
	 * @param string $table
	 * @param array $data
	 * @return int The id of the record
	 */
	public function insert($table, array $data) {
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
	public function update($table, array $data, $where = null, $params = array()) {
		$sql = 'UPDATE ' . $table . " SET \n";
		self::toNamedParams($where, $params);
		foreach ($data as $k => $v) {
			$placeholder = ':' . $k;
			while (isset($params[$placeholder])) {
				$placeholder .= rand(1, 9);
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
	public function delete($table, $where = null, $params = array()) {
		$sql = 'DELETE FROM ' . $table . '';
		$this->injectWhere($sql, $where, $params);
		$stmt = $this->prepare($sql);
		$result = $stmt->execute($params);
		$stmt->closeCursor();
		$stmt = null;
		return $result;
	}

	/**
	 * Explain given sql
	 * 
	 * @param string $sql
	 * @return array 
	 */
	public function explain($sql) {
		if ($this->dbtype == 'mssql') {
			$this->query('SET SHOWPLAN_ALL ON');
		}
		$results = $this->query('EXPLAIN ' . $sql);
		if ($this->dbtype == 'mssql') {
			$this->query('SET SHOWPLAN_ALL OFF');
		}
		return $results->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * Count the records
	 * 
	 * @param string $table
	 * @param array|string $where
	 * @param array $params
	 * @return type 
	 */
	public function count($table, $where = null, $params = array()) {
		$sql = 'SELECT COUNT(*) FROM ' . $table . '';
		$this->inject_where($sql, $where, $params);
		$sql = $this->translate($sql);
		$stmt = $this->prepare($sql);
		$stmt->execute($params);
		$results = $stmt->fetchColumn();
		return (int) $results;
	}

	/**
	 * Find duplicated rows
	 * 
	 * @param string $table
	 * @param string $fields
	 * @return array
	 */
	public function duplicates($table, $fields) {
		$sql = "SELECT $fields, 
COUNT(*) as count
FROM $table
GROUP BY $fields
HAVING ( COUNT(*) > 1 )";
		$results = $this->query($sql);
		if ($results) {
			return $results->fetchAll(PDO::FETCH_ASSOC);
		}
		return array();
	}

	public function max($table, $field = 'id') {
		$sql = "SELECT MAX($field) FROM $table";
		$results = $this->query($sql);
		if ($results) {
			return $results->fetchColumn(0);
		}
		return 0;
	}

	/* table operations */

	/**
	 * Drop table
	 * 
	 * @param string $table
	 * @return int
	 */
	public function dropTable($table, $execute = true) {
		$sql = 'DROP TABLE IF EXISTS ' . $table . '';
		if ($execute) {
			$this->exec($sql);
		}
		return $sql;
	}

	/**
	 * Create constraints based on foreign keys definition array
	 * @param string $table
	 * @param array $keys
	 * @return string
	 * @throws Exception
	 */
	protected function createConstraint($table, $keys) {
		$constraints = array();
		foreach ($keys as $key => $reference) {
			if (is_int($key)) {
				if (is_string($reference)) {
					$constraints[] = $reference;
					continue;
				}
				if (is_array($reference)) {
					$key = $reference['column'];
					$reference = $reference['referenced_table'] . '(' . $reference['referenced_column'] . ')';
				} else {
					throw new Exception('Invalid foreign key definition');
				}
			}
			$fk_name = 'fk_' . $table . '_' . $key . '_' . preg_replace('/[^a-z]/', '', $reference);
			$constraints[] = 'CONSTRAINT ' . $fk_name . ' FOREIGN KEY (' . $key . ') REFERENCES ' . $reference;
		}
		return $constraints;
	}

	/**
	 * Scaffold a create statement
	 * 
	 * fields
	 * ['id','name']
	 * or
	 * ['id' => 'INT', 'name' => 'VARCHAR']
	 * or mixed
	 * 
	 * fkFields
	 * ['table2_id' => 'table2(id)']
	 * or
	 * [['table' => 'table', 'column' => 'table2_id', 'referenced_table' => 'table2', 'referenced_column => 'id']]
	 * 
	 * pkFields
	 * ['id']
	 * 
	 * @param string $table
	 * @param array $fields
	 * @param array $pkFields
	 * @param array $fkFields
	 * @param bool $execute
	 * @return string 
	 */
	public function createTable($table, array $fields = array(), $pkFields = array(), $fkFields = array(), $execute = true) {
		if (is_string($pkFields) && !empty($pkFields)) {
			$pkFields = array($pkFields);
		}

		$dbtype = $this->getDbtype();

		$fields = $this->guessTypes($fields, $pkFields);

		if ($dbtype != 'sqlite' && empty($pkFields) && isset($fields['id'])) {
			$pkFields[] = 'id';
		}

		if (self::isReservedName($table)) {
			throw new Exception($table . ' is a reserved name');
		}
		foreach ($fields as $field => $value) {
			if (self::isReservedName($field)) {
				throw new Exception($field . ' is a reserved name in table ' . $table);
			}
		}

		$sql = 'CREATE TABLE IF NOT EXISTS ' . $table . "(\n";
		foreach ($fields as $field => $type) {
			$sql .= "\t" . $field . ' ' . $type . ",\n";
		}

		//primary key
		if ($dbtype != 'sqlite' || !empty($pkFields)) {
			if (!empty($pkFields)) {
				$sql .= "\t" . 'PRIMARY KEY (' . implode(',', $pkFields) . ')' . ",\n";
			}
		}

		//foreign keys
		if (!empty($fkFields)) {
			$constraints = $this->createConstraint($table, $fkFields);
			$sql .= implode(",\n", $constraints);
		}

		$sql = rtrim($sql, ",\n");

		$sql .= "\n)";

		if ($execute) {
			$this->exec($sql);
		}

		return $sql;
	}

	/**
	 * Sqlite need to recreate a table for alter or change keys
	 * 
	 * @param string $table
	 * @param array $addFields
	 * @param array $removeFields
	 * @param array $addKeys
	 * @param array $removeKeys
	 * @return string
	 */
	protected function sqliteAlter($table, array $addFields = array(), array $removeFields = array(), array $addKeys = array(), array $removeKeys = array()) {
		$tmptable = $table . '_tmp';
		$columns = $this->listColumns($table);
		$fields = array();
		foreach ($columns as $name => $columns) {
			if (in_array($name, $removeFields)) {
				continue;
			}
			$fields[$name] = $columns['type'];
		}
		$newFields = array_merge($fields, $addFields);
		$cols = implode(',', array_keys($fields));

		$foreignKeys = $this->listForeignKeys($table);
		$foreignKeys = array_merge($foreignKeys, $addKeys);
		$foreignKeys = $this->createConstraint($table, $foreignKeys);
		$removeKeys = $this->createConstraint($table, $removeKeys);
		foreach ($removeKeys as $removeKey) {
			$fk = array();
			foreach ($foreignKeys as $key => $reference) {
				if ($reference == $removeKey) {
					continue;
				}
				$fk[] = $reference;
			}
			$foreignKeys = $fk;
		}
		$primaryKeys = $this->listPrimaryKeys($table);

		$sql = "ALTER TABLE $table RENAME TO $tmptable;\n";
		$sql .= $this->createTable($table, $newFields, $primaryKeys, $foreignKeys, false) . ";\n";
		$sql .= "INSERT INTO $table(" . $cols . ") SELECT " . $cols . " FROM $tmptable;\n";
		$sql .= $this->dropTable($tmptable, false);
		return $sql;
	}

	/**
	 * Scaffold an alter table
	 * 
	 * @param string $table
	 * @param array $addFields
	 * @param array $removeFields
	 * @param bool $execute
	 * @return string 
	 */
	public function alterTable($table, array $addFields = array(), array $removeFields = array(), $execute = true) {

		$addFields = $this->guessTypes($addFields);

		if ($this->getDbtype() === self::TYPE_SQLITE) {
			$sql = $this->sqliteAlter($table, $addFields, $removeFields);
			if ($execute) {
				$this->exec($sql);
			}
			return $sql;
		}

		$sql = 'ALTER TABLE ' . $table . "\n";

		foreach ($addFields as $field => $type) {
			if (self::isReservedName($field)) {
				throw new Exception($field . ' is a reserved name');
			}
			$sql .= "ADD COLUMN " . $field . " " . $type . ",\n";
		}

		foreach ($removeFields as $field) {
			$sql .= "DROP COLUMN " . $field . ",\n";
		}

		$sql = rtrim($sql, ",\n");

		if ($execute) {
			$this->exec($sql);
		}

		return $sql;
	}

	/**
	 * Alter charset of a table
	 * 
	 * @param string $table
	 * @param string $charset
	 * @param string $collation
	 * @param bool $execute
	 * @return string 
	 */
	public function alterCharset($table, $charset = 'utf8', $collation = 'utf8_unicode_ci', $execute = true) {
		$sql = 'ALTER TABLE ' . $table . ' MODIFY' . "\n";
		$sql .= 'CHARACTER SET ' . $charset;
		$sql .= 'COLLATE ' . $collation;
		if ($execute) {
			$this->exec($sql);
		}
		return $sql;
	}

	/* Key related helpers */

	/**
	 * Alter keys
	 * 
	 * @param string $table
	 * @param array $keys
	 * @param bool $execute
	 * @return string 
	 */
	public function alterKeys($table, $addKeys = array(), $removeKeys = array(), $execute = true) {
		if (empty($addKeys) && empty($removeKeys)) {
			return false;
		}

		switch ($this->getDbtype()) {
			case 'sqlite':
				$sql = $this->sqliteAlter($table, array(), array(), $addKeys, $removeKeys);

				if ($execute) {
					$this->exec($sql);
				}
				return $sql;
			case 'mysql':
				$res = $this->query("SHOW TABLE STATUS WHERE Name = '$table'");
				if (!$res) {
					return false;
				}
				$rows = $res->fetchAll();
				$rows = $rows[0];
				if (!isset($rows['Engine']) || $rows['Engine'] != 'InnoDB') {
					throw new Exception('Engine ' . $rows['Engine'] . ' does not support foreign keys');
				}

				$all = '';
				$addKeys = $this->createConstraint($table, $addKeys);
				$removeKeys = $this->createConstraint($table, $removeKeys);
				foreach ($addKeys as $constraint) {
					$sql = 'ALTER TABLE ' . $table . "\n";
					$sql .= 'ADD ' . $constraint . ';';
					$all .= $sql . "\n";
					if ($execute) {
						$this->exec($sql);
					}
				}
				foreach ($removeKeys as $constraint) {
					$sql = 'ALTER TABLE ' . $table . "\n";
					preg_match('/CONSTRAINT ([a-z_]*)/', $constraint, $matches);
					$sql .= 'DROP ' . str_replace('CONSTRAINT ', 'FOREIGN KEY ', $matches[0]) . ';';
					$all .= $sql . "\n";
					if ($execute) {
						$this->exec($sql);
					}
				}
				$all = trim($sql, "\n");
				return $all;
			default:
				throw new Exception('Unsupported db type ' . $this->getDbtype());
		}
	}

	/**
	 * Enable or disable foreign key support
	 * 
	 * @param bool $enable
	 * @return bool 
	 */
	public function foreignKeysStatus($enable = true) {
		$dbtype = $this->getDbtype();
		switch ($dbtype) {
			case 'sqlite' :
				if ($enable) {
					return $this->exec('PRAGMA foreign_keys = ON');
				} else {
					return $this->exec('PRAGMA foreign_keys = OFF');
				}
			case 'mysql':
				if ($enable) {
					return $this->exec('SET FOREIGN_KEY_CHECKS = 1');
				} else {
					return $this->exec('SET FOREIGN_KEY_CHECKS = 0');
				}
			case 'mssql' :
				if ($enable) {
					return $this->exec('ALTER TABLE ? NOCHECK CONSTRAINT ALL');
				} else {
					return $this->exec('ALTER TABLE ? CHECK CONSTRAINT ALL');
				}
			default :
				throw new Exception('Unsupported database : ' . $dbtype);
		}
	}

	/**
	 * @internal
	 */
	protected function loadKeys() {
		static $keys;

		if ($keys === null) {
			$sql = "SELECT TABLE_NAME AS `table_name`, COLUMN_NAME AS `column_name`, REFERENCED_COLUMN_NAME AS `referenced_column_name`, REFERENCED_TABLE_NAME AS `referenced_table_name`
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
AND REFERENCED_TABLE_SCHEMA = DATABASE()";
			$result = $this->connection->query($sql);
			$result->setFetchMode(PDO::FETCH_ASSOC);
			$keys = array();
			foreach ($result as $row) {
				$keys[] = $row;
			}
		}
		return $keys;
	}

	/**
	 * Returns list of tables in database.
	 * 
	 * @return array
	 */
	public function listTables() {
		switch ($this->getDbtype()) {
			case 'mysql':
				$sql = "SHOW FULL TABLES WHERE TABLE_TYPE = 'BASE TABLE'";
				break;
			case 'pgsql':
				$sql = "SELECT CONCAT(table_schema,'.',table_name) AS name FROM information_schema.tables 
          WHERE table_type = 'BASE TABLE' AND table_schema NOT IN ('pg_catalog','information_schema')";
				break;
			case 'sqlite':
				$sql = 'SELECT name FROM sqlite_master WHERE type = "table" AND name != "sqlite_sequence"';
				break;
			case 'mssql':
				$sql = "SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'";
			case 'oracle':
				$sql = "SELECT * FROM dba_tables";
			default:
				throw new Exception($this->getDbtype() . ' does not support listing table');
		}
		$result = $this->query($sql);
		$result->setFetchMode(PDO::FETCH_NUM);
		$meta = array();
		foreach ($result as $row) {
			$meta[] = $row[0];
		}
		return $meta;
	}

	/**
	 * Returns reflection information about a table.
	 * 
	 * @param string $table
	 * @return array
	 */
	public function listColumns($table) {
		switch ($this->getDbtype()) {
			case 'pgsql':
				list($schema, $table) = stristr($table, '.') ? explode(".", $table) : array('public', $table);
				$result = $this->query(
						"SELECT c.column_name, c.column_default, c.data_type,
            (SELECT MAX(constraint_type) AS constraint_type FROM information_schema.constraint_column_usage cu
            JOIN information_schema.table_constraints tc ON tc.constraint_name = cu.constraint_name AND tc.constraint_type = 'PRIMARY KEY'
            WHERE cu.column_name = c.column_name AND cu.table_name = c.table_name) AS constraint_type
          FROM information_schema.columns c WHERE c.table_schema = " . $this->quote($schema) . " AND c.table_name = " . $this->quote($table));
				$result->setFetchMode(PDO::FETCH_ASSOC);
				$meta = array();
				foreach ($result as $row) {
					$meta[$row['column_name']] = array(
						'pk' => $row['constraint_type'] == 'PRIMARY KEY',
						'type' => $row['data_type'],
						'blob' => preg_match('/(text|bytea)/', $row['data_type']),
					);
					if (stristr($row['column_default'], 'nextval')) {
						$meta[$row['column_name']]['default'] = null;
					} else if (preg_match("/^'([^']+)'::(.+)$/", $row['column_default'], $match)) {
						$meta[$row['column_name']]['default'] = $match[1];
					} else {
						$meta[$row['column_name']]['default'] = $row['column_default'];
					}
				}
				return $meta;
			case 'sqlite':
				$result = $this->query("PRAGMA table_info(" . $this->quote($table) . ")");
				$result->setFetchMode(PDO::FETCH_ASSOC);
				$meta = array();
				foreach ($result as $row) {
					$meta[$row['name']] = array(
						'pk' => $row['pk'] == '1',
						'type' => $row['type'],
						'default' => null,
						'blob' => preg_match('/(TEXT|BLOB)/', $row['type']),
					);
				}
				return $meta;
			default:
				$result = $this->prepare("select COLUMN_NAME, COLUMN_DEFAULT, DATA_TYPE, COLUMN_KEY 
						from INFORMATION_SCHEMA.COLUMNS 
						where TABLE_SCHEMA = DATABASE() and TABLE_NAME = :table_name");
				$result->setFetchMode(PDO::FETCH_ASSOC);
				$result->execute(array(':table_name' => $table));
				$meta = array();
				foreach ($result as $row) {
					$meta[$row['COLUMN_NAME']] = array(
						'pk' => $row['COLUMN_KEY'] == 'PRI',
						'type' => $row['DATA_TYPE'],
						'default' => in_array($row['COLUMN_DEFAULT'], array('NULL', 'CURRENT_TIMESTAMP')) ? null : $row['COLUMN_DEFAULT'],
						'blob' => preg_match('/(TEXT|BLOB)/', $row['DATA_TYPE']),
					);
				}
				return $meta;
		}
	}

	/**
	 * List primary keys
	 * 
	 * @param string $table
	 * @return array
	 */
	public function listPrimaryKeys($table) {
		$cols = $this->listColumns($table);
		$pk = array();
		foreach ($cols as $name => $meta) {
			if ($meta['pk']) {
				$pk[] = $name;
			}
		}
		return $pk;
	}

	/**
	 * Returns a list of foreign keys for a table.
	 * 
	 * @param string $table
	 * @return array
	 */
	public function listForeignKeys($table) {
		switch ($this->getDbtype()) {
			case 'mysql':
				$meta = array();
				foreach ($this->loadKeys() as $info) {
					if ($info['table_name'] === $table) {
						$meta[] = array(
							'table' => $info['table_name'],
							'column' => $info['column_name'],
							'referenced_table' => $info['referenced_table_name'],
							'referenced_column' => $info['referenced_column_name'],
						);
					}
				}
				return $meta;
			case 'pgsql':
				list($schema, $table) = stristr($table, '.') ? explode(".", $table) : array('public', $table);
				$result = $this->query(
						"SELECT kcu.column_name AS column_name, ccu.table_name AS referenced_table_name, ccu.column_name AS referenced_column_name 
           FROM information_schema.table_constraints AS tc JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name
           JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name WHERE constraint_type = 'FOREIGN KEY' 
           AND tc.table_name='" . $table . "' AND tc.table_schema = '" . $schema . "'");
				$result->setFetchMode(PDO::FETCH_ASSOC);
				$meta = array();
				foreach ($result as $row) {
					$meta[] = array(
						'table' => $table,
						'column' => $row['column_name'],
						'referenced_table' => $row['referenced_table_name'],
						'referenced_column' => $row['referenced_column_name'],
					);
				}
				return $meta;
				break;
			case 'sqlite':
				$sql = "PRAGMA foreign_key_list(" . $this->quote($table) . ")";
				$result = $this->query($sql);
				$result->setFetchMode(PDO::FETCH_ASSOC);
				$meta = array();
				foreach ($result as $row) {
					$meta[] = array(
						'table' => $table,
						'column' => $row['from'],
						'referenced_table' => $row['table'],
						'referenced_column' => $row['to'],
					);
				}
				return $meta;
				break;
			default:
				throw new Exception('Unsupported database : ' . $this->getDbtype());
		}
	}

	/**
	 * Returns a list of foreign keys that refer a table.
	 * 
	 * @param string $table
	 * @return array
	 */
	public function listReferencingKeys($table) {
		switch ($this->getDbtype()) {
			case 'mysql':
				$meta = array();
				foreach ($this->loadKeys() as $info) {
					if ($info['referenced_table_name'] === $table) {
						$meta[] = array(
							'table' => $info['table_name'],
							'column' => $info['column_name'],
							'referenced_table' => $info['referenced_table_name'],
							'referenced_column' => $info['referenced_column_name'],
						);
					}
				}
				return $meta;
			case 'pgsql':
			case 'sqlite':
				$meta = array();
				foreach ($this->listTables() as $tbl) {
					if ($tbl != $table) {
						foreach ($this->listForeignKeys($tbl) as $info) {
							if ($info['referenced_table'] == $table) {
								$meta[] = $info;
							}
						}
					}
				}
				return $meta;
			default:
				throw new Exception('Unsupported database : ' . $this->getDbtype());
		}
	}

	/* helpers */

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
	 * Check if the name is reserved or not (better to avoid them)
	 * 
	 * @param string $name
	 * @return string
	 */
	public static function isReservedName($name) {
		return in_array(strtoupper($name), array(
			"ACCESSIBLE", "ADD", "ALL",
			"ALTER", "ANALYZE", "AND",
			"AS", "ASC", "ASENSITIVE",
			"BEFORE", "BETWEEN", "BIGINT",
			"BINARY", "BLOB", "BOTH",
			"BY", "CALL", "CASCADE",
			"CASE", "CHANGE", "CHAR",
			"CHARACTER", "CHECK", "COLLATE",
			"COLUMN", "CONDITION", "CONSTRAINT",
			"CONTINUE", "CONVERT", "CREATE",
			"CROSS", "CURRENT_DATE", "CURRENT_TIME",
			"CURRENT_TIMESTAMP", "CURRENT_USER", "CURSOR",
			"DATABASE", "DATABASES", "DAY_HOUR",
			"DAY_MICROSECOND", "DAY_MINUTE", "DAY_SECOND",
			"DEC", "DECIMAL", "DECLARE",
			"DEFAULT", "DELAYED", "DELETE",
			"DESC", "DESCRIBE", "DETERMINISTIC",
			"DISTINCT", "DISTINCTROW", "DIV",
			"DOUBLE", "DROP", "DUAL",
			"EACH", "ELSE", "ELSEIF",
			"ENCLOSED", "ESCAPED", "EXISTS",
			"EXIT", "EXPLAIN", "FALSE",
			"FETCH", "FLOAT", "FLOAT4",
			"FLOAT8", "FOR", "FORCE",
			"FOREIGN", "FROM", "FULLTEXT",
			"GRANT", "GROUP", "HAVING",
			"HIGH_PRIORITY", "HOUR_MICROSECOND", "HOUR_MINUTE",
			"HOUR_SECOND", "IF", "IGNORE",
			"IN", "INDEX", "INFILE",
			"INNER", "INOUT", "INSENSITIVE",
			"INSERT", "INT", "INT1",
			"INT2", "INT3", "INT4",
			"INT8", "INTEGER", "INTERVAL",
			"INTO", "IS", "ITERATE",
			"JOIN", "KEY", "KEYS",
			"KILL", "LEADING", "LEAVE",
			"LEFT", "LIKE", "LIMIT",
			"LINEAR", "LINES", "LOAD",
			"LOCALTIME", "LOCALTIMESTAMP", "LOCK",
			"LONG", "LONGBLOB", "LONGTEXT",
			"LOOP", "LOW_PRIORITY", "MASTER_SSL_VERIFY_SERVER_CERT",
			"MATCH", "MAXVALUE", "MEDIUMBLOB",
			"MEDIUMINT", "MEDIUMTEXT", "MIDDLEINT",
			"MINUTE_MICROSECOND", "MINUTE_SECOND", "MOD",
			"MODIFIES", "NATURAL", "NOT",
			"NO_WRITE_TO_BINLOG", "NULL", "NUMERIC",
			"ON", "OPTIMIZE", "OPTION",
			"OPTIONALLY", "OR", "ORDER",
			"OUT", "OUTER", "OUTFILE",
			"PRECISION", "PRIMARY", "PROCEDURE",
			"PURGE", "RANGE", "READ",
			"READS", "READ_WRITE", "REAL",
			"REFERENCES", "REGEXP", "RELEASE",
			"RENAME", "REPEAT", "REPLACE",
			"REQUIRE", "RESIGNAL", "RESTRICT",
			"RETURN", "REVOKE", "RIGHT",
			"RLIKE", "SCHEMA", "SCHEMAS",
			"SECOND_MICROSECOND", "SELECT", "SENSITIVE",
			"SEPARATOR", "SET", "SHOW",
			"SIGNAL", "SMALLINT", "SPATIAL",
			"SPECIFIC", "SQL", "SQLEXCEPTION",
			"SQLSTATE", "SQLWARNING", "SQL_BIG_RESULT",
			"SQL_CALC_FOUND_ROWS", "SQL_SMALL_RESULT", "SSL",
			"STARTING", "STRAIGHT_JOIN", "TABLE",
			"TERMINATED", "THEN", "TINYBLOB",
			"TINYINT", "TINYTEXT", "TO",
			"TRAILING", "TRIGGER", "TRUE",
			"UNDO", "UNION", "UNIQUE",
			"UNLOCK", "UNSIGNED", "UPDATE",
			"USAGE", "USE", "USING",
			"UTC_DATE", "UTC_TIME", "UTC_TIMESTAMP",
			"VALUES", "VARBINARY", "VARCHAR",
			"VARCHARACTER", "VARYING", "WHEN",
			"WHERE", "WHILE", "WITH",
			"WRITE", "XOR", "YEAR_MONTH",
			"ZEROFILL", " ",
			"GENERAL", "IGNORE_SERVER_IDS", "MASTER_HEARTBEAT_PERIOD",
			"MAXVALUE", "RESIGNAL", "SIGNAL",
			"SLOW"
		));
	}

	/**
	 * Guess types from an array of names
	 * Does not overwrite already defined types
	 * 
	 * @param array $arr
	 * @return array
	 */
	public function guessTypes($arr, $pkFields = array()) {
		$fields = array();
		foreach ($arr as $name => $type) {
			if (is_int($name)) {
				$name = $type;
				$type = '';
			}
			if (empty($type)) {
				$type = $this->guessType($name, $pkFields);
			}
			$fields[$name] = $type;
		}
		return $fields;
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
						while (isset($params[$placeholder])) {
							$placeholder .= rand(1, 9);
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
	 * @return Query
	 */
	public function fetchValue($field, $from = null) {
		return $this->q($from)->fields($field)->get('fetchValue');
	}

	/* framework integration */

	/**
	 * Callback for the Dev Toolbar
	 * 
	 * @param \k\dev\Toolbar $tb
	 * @return array
	 */
	public function devToolbarCallback($tb) {
		$arr = array();
		$log = $this->getLog();
		$time = 0;
		foreach ($log as $line) {
			$t = $line['time'];
			$text = $this->highlight($this->formatQuery($line['sql']));
			if ($t) {
				$color = 'Silver';
				if ($t > 0.1) {
					$color = 'PaleTurquoise';
				} elseif ($t > 1) {
					$color = 'deeppink';
				}
				$text = '<span style="color:' . $color . '">[' . $tb->formatTime($t) . ']</span> ' . $text;
			}
			else {
				$text = '<span style="color:deeppink">[error]</span> ' . $text;
			}
			$arr[] = $text;
			$time += $t;
		}
		array_unshift($arr, count($this->getLog()) . ' queries in ' . $tb->formatTime($time));

		return $arr;
	}

	/* formatters */

	/**
	 * Add spacing to a sql string
	 * 
	 * @link http://stackoverflow.com/questions/1191397/regex-to-match-values-not-surrounded-by-another-char
	 * @param string $sql
	 * @return string
	 */
	public function formatQuery($sql) {
		//regex work with a lookahead to avoid splitting things inside single quotes
		$sql = preg_replace(
				"/(WHERE|FROM|GROUP BY|HAVING|ORDER BY|LIMIT|OFFSET|UNION|DUPLICATE KEY)(?=(?:(?:[^']*+'){2})*+[^']*+\z)/", "\n$0", $sql
		);
		$sql = preg_replace(
				"/(INNER|LEFT|RIGHT|CASE|WHEN|END|ELSE|AND)(?=(?:(?:[^']*+'){2})*+[^']*+\z)/", "\n    $0", $sql);
		return $sql;
	}

	/**
	 * Simple sql highlight
	 * 
	 * @param string $sql
	 * @return string
	 */
	public function highlight($sql) {
		$colors = array('chars' => 'Silver', 'keywords' => 'PaleTurquoise', 'joins' => 'Thistle  ', 'functions' => 'MistyRose', 'constants' => 'Wheat');
		$chars = '/([\\.,\\(\\)<>:=`]+)/i';
		$constants = '/(\'[^\']*\'|[0-9]+)/i';
		$keywords = array(
			'SELECT', 'UPDATE', 'INSERT', 'DELETE', 'REPLACE', 'INTO', 'CREATE', 'ALTER', 'TABLE', 'DROP', 'TRUNCATE', 'FROM',
			'ADD', 'CHANGE', 'COLUMN', 'KEY',
			'WHERE', 'ON', 'CASE', 'WHEN', 'THEN', 'END', 'ELSE', 'AS',
			'USING', 'USE', 'INDEX', 'CONSTRAINT', 'REFERENCES', 'DUPLICATE',
			'LIMIT', 'OFFSET', 'SET', 'SHOW', 'STATUS',
			'BETWEEN', 'AND', 'IS', 'NOT', 'OR', 'XOR', 'INTERVAL', 'TOP',
			'GROUP BY', 'ORDER BY', 'DESC', 'ASC', 'COLLATE', 'NAMES', 'UTF8', 'DISTINCT', 'DATABASE',
			'CALC_FOUND_ROWS', 'SQL_NO_CACHE', 'MATCH', 'AGAINST', 'LIKE', 'REGEXP', 'RLIKE',
			'PRIMARY', 'AUTO_INCREMENT', 'DEFAULT', 'IDENTITY', 'VALUES', 'PROCEDURE', 'FUNCTION',
			'TRAN', 'TRANSACTION', 'COMMIT', 'ROLLBACK', 'SAVEPOINT', 'TRIGGER', 'CASCADE',
			'DECLARE', 'CURSOR', 'FOR', 'DEALLOCATE'
		);
		$joins = array('JOIN', 'INNER', 'OUTER', 'FULL', 'NATURAL', 'LEFT', 'RIGHT');
		$functions = array(
			'MIN', 'MAX', 'SUM', 'COUNT', 'AVG', 'CAST', 'COALESCE', 'CHAR_LENGTH', 'LENGTH', 'SUBSTRING',
			'DAY', 'MONTH', 'YEAR', 'DATE_FORMAT', 'CRC32', 'CURDATE', 'SYSDATE', 'NOW', 'GETDATE',
			'FROM_UNIXTIME', 'FROM_DAYS', 'TO_DAYS', 'HOUR', 'IFNULL', 'ISNULL', 'NVL', 'NVL2',
			'INET_ATON', 'INET_NTOA', 'INSTR', 'FOUND_ROWS',
			'LAST_INSERT_ID', 'LCASE', 'LOWER', 'UCASE', 'UPPER',
			'LPAD', 'RPAD', 'RTRIM', 'LTRIM',
			'MD5', 'MINUTE', 'ROUND', 'PRAGMA',
			'SECOND', 'SHA1', 'STDDEV', 'STR_TO_DATE', 'WEEK'
		);

		$sql = str_replace('\\\'', '\\&#039;', $sql);
		foreach ($colors as $key => $color) {
			if (in_array($key, Array('constants', 'chars'))) {
				$regexp = $$key;
			} else {
				$regexp = '/\\b(' . join("|", $$key) . ')\\b/i';
			}
			$sql = preg_replace($regexp, '<span style="color:' . $color . "\">$1</span>", $sql);
		}
		return $sql;
	}

	/**
	 * Guess type of a field according to its name
	 * 
	 * @param string $name
	 * @return string 
	 */
	public function guessType($name, $pkFields = array()) {
		$dbtype = $this->getDbtype();

		$rules = array(
			'zipcode' => 'VARCHAR(20)',
			'_?ip$' => 'VARCHAR(45)', //ipv6 storage
			'^lang_code|country_code$' => 'VARCHAR(2)',
			'_?price$' => 'DECIMAL(10,2) UNSIGNED',
			'_?(id|count|quantity|level|percent|number|sort_order|perms|permissions|day)$' => 'INT',
			'_?(lat|lng|lon|latitude|longitude)$' => 'FLOAT(10,6)',
			'_?(text|content)$' => 'TEXT',
			'_?(guid|uuid)$' => 'BINARY(36)', //don't store charset/collation
			//dates
			'^(is|has)_' => 'TINYINT',
			'_?(datetime|at)$' => 'DATETIME',
			'_?(date|birthdate|birthday)$' => 'DATE',
			'_?time$' => 'TIME',
			'_?ts$' => 'TIMESTAMP',
		);

		//default type
		$type = 'VARCHAR(255)';

		//guess by name
		if (empty($pkFields) && $name == 'id') {
			if ($dbtype == 'sqlite') {
				$type = 'INTEGER PRIMARY KEY AUTOINCREMENT';
			} else {
				$type = 'INT AUTO_INCREMENT';
			}
			return $type;
		}

		foreach ($rules as $r => $t) {
			if (preg_match('/' . $r . '/', $name)) {
				return $t;
			}
		}

		return $type;
	}

	/* factories */

	/**
	 * Alias query
	 * 
	 * @param string $from
	 * @return \k\db\Query
	 */
	public function q($from = null) {
		return $this->getQuery($from);
	}

	/**
	 * Get a select query builder
	 * 
	 * @param string $from
	 * @return \k\db\Query
	 */
	public function getQuery($from = null) {
		return Query::create($this)->from($from);
	}

	/**
	 * Alias table
	 * 
	 * @param string|object $table
	 * @return \k\db\Table
	 */
	public function t($table) {
		return $this->getTable($table);
	}

	/**
	 * Return a table from the database
	 * @param string|object $table
	 * @return \k\db\Table
	 */
	public function getTable($table) {
		if (!isset($this->tables[$table])) {
			$this->tables[$table] = new Table($table, $this);
		}
		return $this->tables[$table];
	}

}