<?php

namespace k\sql;

use \PDO as NativePdo;
use \PDOException as NativePdoException;

/**
 * Pdo extension. The class extends PDO to allow itself to pass
 * as an instance of PDO.
 *
 * @author lekoala
 */
class Pdo extends NativePdo {

	/**
	 * Inner pdo instance to allow lazy connection
	 * @var NativePdo
	 */
	protected $pdo = null;

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
	 * Highlight colors
	 * @var array
	 */
	protected $colors = array('chars' => 'Silver', 'keywords' => 'PaleTurquoise', 'joins' => 'Thistle  ', 'functions' => 'MistyRose', 'constants' => 'Wheat');

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

		$this->setDsn($dsn);
		$this->setDbtype($dbtype);
		if (isset($dbname)) {
			$this->setDbname($dbname);
		}
		$this->setUser($user);
		$this->setPassword($password);
		$this->setOptions($options);
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
		$this->setDsn($dsn);
		$this->setUser($user);
		$this->setPassword($password);
		$this->setOptions($options);

		$this->pdo = new NativePdo($dsn, $user, $password, $options);

		//always throw exception
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		//use custom pdo statement class
		$this->pdo->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('\k\sql\PdoStatement', array($this)));
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

	public function getLogger() {
		return $this->logger;
	}

	public function setLogger($logger) {
		$this->logger = $logger;
		return $this;
	}

	public function getColors() {
		return $this->colors;
	}

	public function setColors($colors) {
		$this->colors = $colors;
		return $this;
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
		if($results) {
			return $results->fetchColumn(0); 
		}
		return 0;
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
			$text = $this->highlight($this->formatQuery($line['sql']));
			if ($line['time']) {
				$text = '[' . $tb->formatTime($line['time']) . '] ' . $text;
			}
			$arr[] = $text;
			$time += $line['time'];
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
		$colors = $this->colors;
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

	/* factories */

	/**
	 * Alias query
	 * 
	 * @param string $from
	 * @return \k\sql\Query
	 */
	public function q($from = null) {
		return $this->getQuery($from);
	}

	/**
	 * Get a select query builder
	 * 
	 * @param string $from
	 * @return \k\sql\Query
	 */
	public function getQuery($from = null) {
		return Query::create($this)->from($from);
	}

	/**
	 * Alias table
	 * 
	 * @param string|object $table
	 * @return \k\sql\Table
	 */
	public function t($table) {
		return $this->getTable($table);
	}

	/**
	 * Return a table from the database
	 * @param string|object $table
	 * @return \k\sql\Table
	 */
	public function getTable($table) {
		if (!isset($this->tables[$table])) {
			$this->tables[$table] = new Table($table, $this);
		}
		return $this->tables[$table];
	}

}