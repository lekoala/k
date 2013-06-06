<?php

namespace k\db;

use \PDO as NativePdo;
use \PDOException as NativePdoException;

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
 * @author lekoala
 */
class Pdo extends NativePdo {

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
			self::$instances[$name] = new self();
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
			throw new PdoException($e);
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
			throw new PdoException($e);
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
		$sql = $this->translate($sql);
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
	public function dropTable($table) {
		$sql = 'DROP TABLE ' . $table . '';
		return $this->exec($sql);
	}

	/**
	 * Scaffold a create statement
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

		$fields = $this->guessTypes($fields, $pkFields);

		if (self::isReservedName($table)) {
			throw new Exception($table . ' is a reserved name');
		}
		foreach ($fields as $field => $value) {
			if (self::isReservedName($field)) {
				throw new Exception($field . ' is a reserved name in table ' . $table);
			}
		}

		$dbtype = $this->getDbtype();

		$sql = 'CREATE TABLE IF NOT EXISTS ' . $table . "(\n";
		foreach ($fields as $field => $type) {
			$sql .= "\t" . $field . ' ' . $type . ",\n";
		}

		//primary key
		if ($dbtype != 'sqlite') {
			if (!empty($pkFields)) {
				$sql .= "\t" . 'PRIMARY KEY (' . implode(',', $pkFields) . ')' . ",\n";
			}
		}

		//foreign keys
		foreach ($fkFields as $key => $reference) {
			$fk_name = 'fk_' . $table . '_' . preg_replace('/[^a-z]/', '', $reference);
			$sql .= "\t" . 'CONSTRAINT ' . $fk_name . ' FOREIGN KEY (' . $key . ') REFERENCES ' . $reference . ",\n";
		}

		$sql = rtrim($sql, ",\n");

		$sql .= "\n)";

		if ($execute) {
			$this->exec($sql);
		}

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
	 * All columns from a table
	 * 
	 * @param string $table
	 * @return array
	 */
	public function listColumns($table) {
		$sql = 'SELECT * FROM ' . $table . ' LIMIT 1';

		$stmt = $this->query($sql);

		$i = 0;
		$infos = array();
		while ($column = $stmt->getColumnMeta($i++)) {
			$infos[$column['name']] = $column;
		}
		return $infos;
	}

	/**
	 * Cross database list tables
	 * 
	 * @return array 
	 */
	public function listTables() {
		// use database specific statement to get the list of tables
		$mysql = 'SHOW FULL TABLES';
		$pgsql = 'SELECT * FROM pg_tables';
		$mssql = "SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'";
		$sqlite = "SELECT * FROM sqlite_master WHERE type='table'";
		$oracle = "SELECT * FROM dba_tables";

		$type = $this->getDbtype();

		$result = $this->query($$type);
		$list = $result->fetchAll();
		$count = count($list);

		//normalize results
		switch ($type) {
			case 'mysql':
				$tables = array();
				for ($i = 0; $i < $count; $i++) {
					$tables[] = $list[$i][0];
				}
				$list = $tables;
				break;
		}

		return $list;
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
	public function alterKeys($table, $keys, $execute = true) {
		if (empty($keys)) {
			return false;
		}
		$res = $this->query("SHOW TABLE STATUS WHERE Name = '$table'");
		if (!$res) {
			return false;
		}
		$rows = $res->fetchAll();
		if (!isset($rows['Engine']) || $rows['Engine'] != 'InnoDb') {
			return false;
		}

		$all = '';
		foreach ($keys as $key => $reference) {
			$sql = 'ALTER TABLE ' . $table . "\n";
			$fk_name = 'fk_' . $table . '_' . preg_replace('/[^a-z]/', '', $reference);
			$sql .= 'ADD CONSTRAINT ' . $fk_name . ' FOREIGN KEY (' . $key . ') REFERENCES ' . $reference;
			$all .= $sql . ";\n";
			if ($execute) {
				$this->exec($sql);
			}
		}
		$all = trim($sql, "\n");
		return $all;
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
	 * List foreign keys querying information schema
	 * 
	 * Return something like
	 * Array(
	 * 	[0] => Array(
	 * 	[column_name] => 'name'
	 *  [foreign_db] => 'db,
	 *  [foreign_table] => 'company',
	 *  [foreign_column] => 'id'
	 * 	)
	 * )
	 * @return array
	 */
	public function listForeignKeys($table) {
		$res = $this->query("SHOW TABLE STATUS WHERE Name = '$table'");
		if (!$res) {
			return false;
		}
		$query = "SELECT
    `column_name`, 
    `referenced_table_schema` AS foreign_db, 
    `referenced_table_name` AS foreign_table, 
    `referenced_column_name`  AS foreign_column 
FROM
    `information_schema`.`KEY_COLUMN_USAGE`
WHERE
    `constraint_schema` = SCHEMA()
AND
    `table_name` = '$table'
AND
    `referenced_column_name` IS NOT NULL
ORDER BY
    `column_name`";
		$res = $this->query($query);
		return $res->fetchAll(PDO::FETCH_ASSOC);
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
	public function guessTypes($arr) {
		$fields = array();
		foreach ($arr as $name => $type) {
			if (is_int($name)) {
				$name = $type;
				$type = '';
			}
			if (empty($type)) {
				$type = $this->guessType($name);
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
	 * @param DevToolbar $tb
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
				}
				elseif ($t > 1) {
					$color = 'Red';
				}
				$text = '<span style="color:' . $color . '">[' . $tb->formatTime($t) . ']</span> ' . $text;
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
	public function guessType($name) {
		$dbtype = $this->getDbtype();

		$rules = array(
			'zipcode' => 'VARCHAR(20)',
			'_?ip$' => 'VARCHAR(45)', //ipv6 storage
			'lang_code|country_code' => 'VARCHAR(2)',
			'_?price$' => 'DECIMAL(10,2) UNSIGNED',
			'_?(id|count|quantity|level|percent|number|sort_order|perms|permissions|day)$' => 'INT',
			'_?(lat|lng|lon|latitude|longitude)$' => 'FLOAT(10,6)',
			'_?(text|content)' => 'TEXT',
			'_?(guid|uuid)$' => 'BINARY(36)', //don't store charset/collation
			//dates
			'^(is|has)_' => 'TINYINT',
			'_?(datetime|at)$' => 'DATETIME',
			'_?(date|birthdate|birthday)$' => 'DATE',
			'_?time' => 'TIME',
			'_?ts' => 'TIMESTAMP',
		);

		//default type
		$type = 'VARCHAR(255)';

		//guess by name
		if ($name == 'id') {
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