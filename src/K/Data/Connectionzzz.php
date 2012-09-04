<?php

namespace K\Db;

use \PDO as PDO_Base;
use \PDOException;

/**
 * Simple PDO wrapper used to provide more control over PDO and bundle some
 * helper functions
 */
class Connection {

	/**
	 * All queries made
	 * @var array 
	 */
	static $queries = array();

	/**
	 * Total time
	 * @var int
	 */
	static $time = 0;
	
	/**
	 * Driver
	 * @var K\Db\Driver_Interface
	 */
	protected $driver;

	/**
	 * The connection string
	 * @var string
	 */
	protected $dsn;

	/**
	 * Db type
	 * @var string
	 */
	protected $dbtype;

	/**
	 * Username
	 * @var string 
	 */
	protected $username;

	/**
	 * Password
	 * @var string
	 */
	protected $password;

	/**
	 * Driver specific options
	 * @var array
	 */
	protected $options;

	/**
	 * Reserved names that should not be used
	 * @var array
	 */
	protected static $reservedNames = array(
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
	);
	

	/**
	 * A smarter constructor for PDO. You can pass everything in the first argument
	 * as an array or use it as usual
	 * 
	 * @param string|array $dsn
	 * @param string $username
	 * @param string $password
	 * @param array $options 
	 */
	function __construct($dsn = array(), array $options = array()) {
		if(is_string($dsn)) {
			$driver = substr($dsn, 0,  strpos($dsn, ':'));
		}
		else {
			$driver = $dsn['driver'];
		}
		
		$driverClass = 'Driver_' . ucfirst($driver);
		$this->driver = new $driverClass($dsn,$options);
	}

	/* Overriden methods */

	/**
	 * Exec wrapper for stats
	 * 
	 * @param string $statement
	 * @return int
	 */
	function exec($statement) {
		try {
			$time = microtime(true);
			$result = parent::exec($statement);
			$time = microtime(true) - $time;
			self::logQuery($statement, $time);
		} catch (PDOException $e) {
			throw new Exception($e);
		}
		return $result;
	}

	/**
	 * Query wrapper for stats
	 * 
	 * @param string $statement
	 * @return _pdo_statement
	 */
	function query($statement) {
		try {
			$time = microtime(true);
			$result = parent::query($statement);
			$time = microtime(true) - $time;
			self::logQuery($statement, $time);
		} catch (PDOException $e) {
			throw new Exception($e);
		}

		return $result;
	}

	/**
	 * More advanced quote (quote arrays, return NULL properly, quotes INT properly...)
	 * 
	 * @param string $value
	 * @param int $parameter_type
	 * @return string 
	 */
	function quote($value, $parameter_type = null) {
		if (is_array($value)) {
			$value = implode(',', array_map(array($this, 'quote'), $value));
			return $value;
		} elseif (is_null($value)) {
			return "NULL";
		} elseif (($value !== true) && ((string) (int) $value) === ((string) $value)) {
			//otherwise int will be quoted, also see @https://bugs.php.net/bug.php?id=44639
			return parent::quote(intval($value), PDO::PARAM_INT);
		}
		$parameter_type = PDO::PARAM_STR;
		return parent::quote($value, $parameter_type);
	}

	/* Helper methods */

	/**
	 * Get db type
	 * 
	 * @return string
	 */
	function getDbtype() {
		return $this->dbtype;
	}

	/**
	 * Cross database now string
	 * 
	 * @return string
	 */
	function now() {
		$dbtype = $this->getDbtype();
		switch ($dbtype) {
			case 'sqlite' :
				return "datetime('now')";
			case 'mssql' :
				return 'GETDATE()';
			default :
				return 'NOW()';
		}
	}

	/**
	 * Enable or disable foreign key support
	 * 
	 * @param bool $enable
	 * @return bool 
	 */
	function foreignKeys($enable = true) {
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
	 * Check if the table or the field is reserved
	 * 
	 * @param string $name
	 * @return bool
	 */
	static function isReservedName($name) {
		if (in_array(strtoupper($name), self::$reserved_names)) {
			return true;
		}
		return false;
	}

	/**
	 * Highlight keywords in sql
	 * 
	 * @param string $sql
	 * @return string
	 */
	static function highlight($sql) {
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
	function nameToType($name) {
		$dbtype = $this->getDbtype();

		//default type
		$type = 'VARCHAR(255)';

		//guess by name, latest rule override previous ones
		if ($name == 'id') {
			if ($dbtype == 'sqlite') {
				$type = 'INTEGER PRIMARY KEY AUTOINCREMENT';
			} else {
				$type = 'INT AUTO_INCREMENT';
			}
		}
		//guid
		elseif ($name == 'guid'
				|| $name == 'uiid'
				|| strpos($name, '_guid') !== false
				|| strpos($name, '_uiid') !== false) {
			$type = 'BINARY(36)'; //don't store charset/collation
		}
		//varchar
		elseif ($name == 'name') {
			$type = 'VARCHAR(45)';
		} elseif ($name == 'zipcode') {
			$type = 'VARCHAR(20)';
		} elseif (strpos($name, 'ip') === 0
				|| strpos($name, '_ip') !== false) {
			$type = 'VARCHAR(45)'; //ipv6 storage
		}
		//price
		elseif (strpos($name, 'price') !== false
				|| strpos($name, '_price')) {
			$type = 'DECIMAL(10,2) UNSIGNED';
		}
		//int
		elseif (strpos($name, '_id') !== false
				|| strpos($name, '_count') !== false
				|| $name == 'quantity'
				|| $name == 'sort_order'
				|| $name == 'permissions'
				|| $name == 'perms'
				|| $name == 'day') {
			$type = 'INT';
		}
		//geo
		elseif ($name == 'lat'
				|| $name == 'lng'
				|| $name == 'latitude'
				|| $name == 'longitude') {
			$type = 'FLOAT(10,6)';
		}
		//bool
		elseif (strpos($name, 'is_') === 0
				|| strpos($name, 'has_') === 0) {
			$type = 'TINYINT';
		}
		//date
		elseif ($name == 'datetime'
				|| $name == 'birthday'
				|| strpos($name, '_at') !== false
		) {
			$type = 'DATETIME';
		} elseif ($name == 'date'
				|| strpos($name, '_date') !== false
				|| strpos($name, 'date_') === 0) {
			$type = 'DATE';
		} elseif ($name == 'time'
				|| strpos($name, '_time') !== false
				|| strpos($name, 'time_') === 0) {
			$type = 'TIME';
		} elseif (strpos($name, '_ts') !== false) {
			$type = 'TIMESTAMP';
		}
		//text
		elseif (strpos($name, '_html') !== false
				|| strpos($name, '_text') !== false
				|| $name == 'content') {
			$type = 'TEXT';
		}
		return $type;
	}

	/**
	 * All columns from a table
	 * 
	 * @param string $table
	 * @param bool $normalize (optional) default true
	 * @return array
	 */
	function listColumns($table, $normalize = true) {
		$sqlite = "PRAGMA table_info($table)";
		$default = "SELECT * FROM ' . $table . ' LIMIT 1";

		if ($dbtype == 'sqlite') {
			$result = $this->query($sqlite);
		} else {
			$result = $this->query($default);
		}
		$meta_list = $result->fetchAll();

		if ($normalize) {
			switch ($dbtype) {
				case 'sqlite' :
					return array_map(function($item) {
										return $item['name'];
									}, $meta_list);
				default :
					$i = 0;
					$infos = array();
					while ($column = $meta_list->getColumnMeta($i++)) {
						$infos[] = $column['name'];
					}
					return $info;
			}
		}

		return $meta_list;
	}

	/**
	 * Cross database list tables
	 * 
	 * @return array 
	 */
	function listTables() {
		// use database specific statement to get the list of tables
		$mysql = 'SHOW FULL TABLES';
		$pgsql = 'SELECT * FROM pg_tables';
		$mssql = "SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'";
		$sqlite = "SELECT * FROM sqlite_master WHERE type='table'";
		$oracle = "SELECT * FROM dba_tables";

		$type = $this->getDbtype();

		$result = $this->query($$type);
		$table_list = $result->fetchAll();
		$table_count = count($table_list);

		//normalize results
		switch ($type) {
			case 'mysql':
				$tables = array();
				for ($i = 0; $i < $table_count; $i++) {
					$tables[] = $table_list[$i][0];
				}
				$table_list = $tables;
				break;
			case 'sqlite' :
				$tables = array();
				for ($i = 0; $i < $table_count; $i++) {
					$tables[] = $table_list[$i]['name'];
				}
				$table_list = $tables;
		}

		return $table_list;
	}

	/**
	 * Log query
	 * 
	 * @param string $statement
	 * @param int $time 
	 */
	static function logQuery($statement, $time = 0) {
		$statement = self::highlight($statement);
		if ($time == 0) {
			self::$queries[] = '<span style="color:#ff9292">[ERROR]</span> ' . $statement;
		} else {
			self::$queries[] = '[' . sprintf('%0.6f', $time) . '] ' . $statement;
			self::$time += $time;
		}
	}

	/**
	 * Callback for DebugBar
	 * 
	 * @return string 
	 */
	static function debugBarCallback() {
		$total_queries = count(self::$queries);
		$time = self::$time;
		$html = 'Total queries : ' . $total_queries . ' (' . sprintf('%0.6f', $time) . ' s)';

		$limit = 100;
		$length = count(self::$queries);
		$queries = '';
		for ($i = 0; $i < $limit && $i < $length; $i++) {
			$queries .= self::$queries[$i] . '<br/>';
			if ($i == $limit) {
				$queries .= 'Only showing 100 first queries';
			}
		}

		return $data;
	}

	/* sql helpers */

	/**
	 * Insert records
	 * 
	 * @param string $table
	 * @param array $data
	 * @param array $params
	 * @return int The id of the record
	 */
	function insert($table, array $data, $params = array()) {
		foreach ($data as $k => $v) {
			$keys[] = $k;
			$values[] = ':' . $k;
			$params[':' . $k] = $v;
		}

		$sql = "INSERT INTO " . $table . " (" . implode(",", $keys) . ") VALUES (" . implode(',', $values) . ")";
		$stmt = $this->prepare($sql);
		$result = $stmt->execute($params);
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
			$sql .= $k . ' = :' . $k . ', ';
			$params[':' . $k] = $v;
		}
		$sql = rtrim($sql, ', ');
		$this->injectWhere($sql, $where, $params);

		$stmt = $this->prepare($sql);
		$result = $stmt->execute($params);
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
		return $result;
	}

	/**
	 * Select records
	 * 
	 * @param string $table
	 * @param array|string $where
	 * @param array|string $order_by
	 * @param array|string $limit
	 * @param array|string $fields
	 * @param array $params
	 * @return array 
	 */
	function select($table, $where = null, $order_by = null, $limit = null, $fields = '*', $params = array()) {
		$stmt = $this->selectStmt($table, $where, $order_by, $limit, $fields, $params);
		$stmt->execute($params);

		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $results;
	}

	/**
	 * Explain given sql
	 * 
	 * @param string $sql
	 * @return array 
	 */
	function explain($sql) {
		if ($this->getDbtype() == 'mssql') {
			$this->query('SET SHOWPLAN_ALL ON');
		}
		$results = $this->query('EXPLAIN ' . $sql);
		if ($this->getDbtype() == 'mssql') {
			$this->query('SET SHOWPLAN_ALL OFF');
		}
		return $results->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * Find duplicated rows
	 * 
	 * @param string $table
	 * @param string $field
	 * @param string $fields
	 * @return array
	 */
	function duplicates($table, $field, $fields = '*') {
		$sql = "SELECT $fields FROM $table
WHERE $field IN (
SELECT $field
  FROM $table
  GROUP BY $field
  HAVING count(*) > 1
)";
		$results = $this->query($sql);
		if ($results) {
			return $results->fetchAll(PDO::FETCH_ASSOC);
		}
		return array();
	}

	/**
	 * Create a select statement
	 * 
	 * Note : updated parameters will be placed in the $params through reference
	 * 
	 * @param string $table
	 * @param array|string $where
	 * @param array|string $order_by
	 * @param array|string $limit
	 * @param array|string $fields
	 * @param array $params
	 * @return _pdo_statement 
	 */
	function selectStmt($table, $where = null, $order_by = null, $limit = null, $fields = '*', &$params = array()) {
		if (is_array($fields)) {
			$fields = implode(',', $fields);
		}
		$sql = 'SELECT ' . $fields . ' FROM ' . $table . '';
		$this->inject_where($sql, $where, $params);
		if (!empty($order_by)) {
			if (is_array($order_by)) {
				$order_by = implode(',', $order_by);
			}
			$sql .= ' ORDER BY ' . $order_by;
		}
		if (!empty($limit)) {
			if (is_array($limit)) {
				$limit = implode(',', $limit);
			}
			$sql .= ' LIMIT ' . $limit;
		}
		$stmt = $this->prepare($sql);
		return $stmt;
	}

	/**
	 * Count the records
	 * 
	 * @param string $table
	 * @param array|string $where
	 * @param array $params
	 * @return type 
	 */
	function count($table, $where = null, $params = array()) {
		$sql = 'SELECT COUNT(*) FROM ' . $table . '';
		$this->injectWhere($sql, $where, $params);
		$stmt = $this->prepare($sql);
		$stmt->execute($params);
		$results = $stmt->fetchColumn();
		return (int) $results;
	}

	/**
	 * A quick fix to convert ? to named params
	 * 
	 * @param string $where
	 * @param array $params 
	 */
	protected static function toNamedParams(&$where, array &$params) {
		if (is_string($where) && preg_match('/\?/', $where, $matches)) {
			$matches_count = count($matches);
			$named_params = array();
			for ($i = 0; $i < $matches_count; $i++) {
				$where = preg_replace('/\?/', ':placeholder' . $i, $where, 1);
				$named_params[':placeholder' . $i] = $params[$i];
			}
			$params = $named_params;
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
						if (is_array($item)) {
							$item = array_unique($item);
							$item = $key . " IN (" . $pdo->quote($item) . ")";
						} elseif (is_string($key)) {
							$params[':' . $key] = $item;
							$item = $key . " = :" . $key;
						} else {
							$item = $item . " = :" . $item;
						}
					});
			$where = implode(' AND ', $where);
		}
		if (!empty($where)) {
			$sql .= ' WHERE ' . $where;
		}
		return $sql;
	}

	public static function getFkName($table, $column, $foreignColumn) {
		return 'fk_' . $table . '_' . $column . '_' . $foreignColumn;
	}

	/* Table operations */

	/**
	 * Empty the table from all records
	 * 
	 * @param string $table
	 * @param bool $truncate
	 * @return int
	 */
	function emptyTable($table, $truncate = false) {
		$sql = 'DELETE FROM ' . $table . '';
		if ($truncate) {
			$sql = 'TRUNCATE ' . $table . '';
		}
		return $this->exec($sql);
	}

	/**
	 * Drop table
	 * 
	 * @param string $table
	 * @return int
	 */
	function dropTable($table) {
		$sql = 'DROP TABLE ' . $table . '';
		return $this->exec($sql);
	}

	/**
	 * Scaffold a create statement
	 * 
	 * @param string $table
	 * @param array $fields
	 * @param array $pk_fields
	 * @param array $fk_fields
	 * @param bool $execute
	 * @return string 
	 */
	function createTable($table, array $fields = array(), $pk_fields = array(), $fk_fields = array(), $execute = true) {
		if (is_string($pk_fields) && !empty($pk_fields)) {
			$pk_fields = array($pk_fields);
		}

		$fields = $this->addFieldType($fields, $pk_fields);

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
		if (!empty($pk_fields)) {
			if ($dbtype != 'sqlite') {
				$sql .= "\t" . 'PRIMARY KEY (' . implode(',', $pk_fields) . ')' . ",\n";
			}
			else {
				
			}
		}

		//foreign keys
		foreach ($fk_fields as $column => $reference) {
			$fk_name = self::getFkName($table, $column, $reference);
			$sql .= "\t" . 'CONSTRAINT ' . $fk_name . ' FOREIGN KEY (' . $column . ') REFERENCES ' . $reference . ",\n";
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
	 * @param array $add_fields
	 * @param array $remove_fields
	 * @param bool $execute
	 * @return string 
	 */
	function alterTable($table, array $add_fields = array(), array $remove_fields = array(), $execute = true) {

		$add_fields = $this->addFieldType($add_fields);

		$sql = 'ALTER TABLE ' . $table . "\n";

		foreach ($add_fields as $field => $type) {
			if (self::isReservedName($field)) {
				throw Exception($field . ' is a reserved name');
			}
			$sql .= "ADD COLUMN " . $field . " " . $type . ",\n";
		}

		foreach ($remove_fields as $field) {
			$sql .= "DROP COLUMN " . $field . ",\n";
		}

		$sql = rtrim($sql, ",\n");

		if ($execute) {
			$this->exec($sql);
		}

		return $sql;
	}

	/**
	 * Guess type to field definitions based on field name
	 * 
	 * @param array $fields
	 * @param array $pk_fields
	 * @return array 
	 */
	function addFieldType(array $fields, array $pk_fields = array()) {
		//do not type already typed fields
		foreach ($fields as $k => $v) {
			if (!is_int($k)) {
				return $fields;
			}
		}

		$fields_type = array();
		$dbtype = $this->getDbtype();
		foreach ($fields as $field) {
			$type = $this->nameToType($field);
			if ($dbtype == 'sqlite' && in_array($field, $pk_fields) && strpos($type, 'PRIMARY KEY') === false) {
				//add primary key for sqlite if not already there
				$type .= ' PRIMARY KEY';
			}
			$fields_type[$field] = $type;
		}
		return $fields_type;
	}

	/* view operations */

	/**
	 * Create a view
	 * 
	 * @param string $view View name without v_ prefix
	 * @param bool $execute
	 * @return string 
	 */
	function createView($view, $select, $execute = true) {
		$name = 'v_' . $view;
		$dbtype = $this->getDbtype();

		$select = str_replace('SELECT ', '', $select);

		if ($dbtype == 'mysql') {
			$sql = 'CREATE OR REPLACE VIEW ' . $name . " AS SELECT \n";
		} else if ($dbtype == 'sqlite') {
			$sql = 'CREATE VIEW ' . $name . " IF NOT EXISTS AS SELECT \n";
		} else {
			$sql = 'CREATE VIEW ' . $name . " AS SELECT \n";
		}

		$sql .= $select;

		if ($execute) {
			$this->exec($sql);
		}

		return $sql;
	}

	/**
	 * Drop a view
	 * 
	 * @param string $view View name without v_ prefix
	 * @param bool $execute 
	 */
	function dropView($view, $execute = true) {
		$name = 'v_' . $view;
		$sql = 'DROP VIEW ' . $name;

		if ($execute) {
			$this->exec($sql);
		}

		return $sql;
	}

}