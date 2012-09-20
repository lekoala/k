<?php

namespace K;

/**
 * Simple PDO wrapper used to provide more control over PDO and bundle some
 * helper functions
 */
class Pdo extends \PDO {

	const SQLITE_MEMORY = 'sqlite::memory:';

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
	 * The connection string
	 * @var string
	 */
	protected $dsn;

	/**
	 * Db driver
	 * @var string
	 */
	protected $driver;

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
	 * Cache function
	 * @var bool
	 */
	protected $supportsForeignKey;

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
	function __construct($dsn, $username = null, $password = null, array $options = array()) {
		if (is_array($dsn)) {
			//extract params
			$params = array('username', 'password', 'options', 'driver');
			foreach ($params as $param) {
				if (isset($dsn[$param])) {
					$$param = $dsn[$param];
					unset($dsn[$param]);
				}
			}
			//flatten array, except for sqlite which pass just a string without keys
			foreach ($dsn as $k => $v) {
				if (!is_int($k)) {
					$dsn[$k] = $k . '=' . $v;
				}
			}

			//{dbtype}:dbname={dbname};host={host};port={port}
			$dsn = $driver . ':' . implode(';', array_values($dsn));
		} else {
			$params = $this->parseDsn($dsn);
			extract($params);
		}

		$this->dsn = $dsn;
		$this->driver = $driver;
		$this->username = $username;
		$this->password = $password;
		$this->options = $options;

		try {
			parent::__construct($dsn, $username, $password, $options);
		} catch (\PDOException $e) {
			throw new PdoException($e);
		}

		//always throw exception
		$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		//use custom pdo statement class
		$this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('\K\PdoStatement', array($this)));
	}

	/**
	 * Parse a dsn
	 * 
	 * @param string $dsn
	 * @return array
	 */
	protected static function parseDsn($dsn) {
		if (strpos($dsn, 'sqlite') === 0) {
			$driver = 'sqlite';
			$database = substr($dsn, strpos($dsn, ':') + 1, strlen($dsn));
			return compact('driver', 'database');
		}

		preg_match('%^([^/]+)?://?(?:([^/@]*?)(?::([^/@:]+?)@)?([^/@:]+?)(?::([^/@:]+?))?)?/(.+)$%', $dsn, $matches);
		return array(
			'driver' => isset($matches[1]) ? $matches[1] : null,
			'username' => isset($matches[2]) ? $matches[2] : null,
			'password' => isset($matches[3]) ? $matches[3] : null,
			'host' => isset($matches[4]) ? $matches[4] : null,
			'port' => isset($matches[5]) ? $matches[5] : null,
			'database' => isset($matches[6]) ? $matches[6] : null
		);
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
		} catch (\PDOException $e) {
			self::logQuery($statement);
			throw new PdoException($e);
		}
		return $result;
	}

	/**
	 * Query wrapper for stats
	 * 
	 * @param string $statement
	 * @return PdoStatement
	 */
	function query($statement) {
		try {
			$time = microtime(true);
			$result = parent::query($statement);
			$time = microtime(true) - $time;
			self::logQuery($statement, $time);
		} catch (\PDOException $e) {
			self::logQuery($statement);
			throw new PdoException($e);
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
	function getDriver() {
		return $this->driver;
	}

	/**
	 * Check if the engine supports foreign key
	 * @return bool
	 */
	function getForeignKeySupport() {
		if ($this->supportsForeignKey !== null) {
			return $this->supportsForeignKey;
		}
		switch ($this->getDriver()) {
			case 'mysql':
				$res = $this->query("SHOW TABLE STATUS WHERE Name = '$table'");
				if (!$res) {
					return false;
				}
				$rows = $res->fetchAll();
				$this->supportsForeignKey = false;
				if (isset($rows['Engine']) && $rows['Engine'] != 'InnoDb') {
					$this->supportsForeignKey = true;
				}
				break;
			case 'sqlite' :
				$this->supportsForeignKey = true;
				break;
			default:
				$this->supportsForeignKey = false;
		}
		return $this->supportsForeignKey;
	}

	/**
	 * Cross database now string
	 * 
	 * @return string
	 */
	function now() {
		$driver = $this->getDriver();
		switch ($driver) {
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
		$driver = $this->getDriver();
		switch ($driver) {
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
				throw new PdoException('Unsupported database : ' . $driver);
		}
	}

	/**
	 * Check if the table or the field is reserved
	 * 
	 * @param string $name
	 * @return bool
	 */
	static function isReservedName($name) {
		if (in_array(strtoupper($name), self::$reservedNames)) {
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
		$driver = $this->getDriver();

		//default type
		$type = 'VARCHAR(255)';

		//guess by name, latest rule override previous ones
		if ($name == 'id') {
			if ($driver == 'sqlite') {
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
		elseif ($name == 'zipcode') {
			$type = 'VARCHAR(20)';
		} elseif (strpos($name, 'ip') === 0
				|| strpos($name, '_ip') !== false) {
			$type = 'VARCHAR(45)'; //ipv6 storage
		}
		//char
		elseif ($name == 'code'
				|| $name == 'iso'
				|| $name == 'iso2') {
			$type = 'CHAR(2)';
		} elseif ($name == 'iso3') {
			$type = 'CHAR(3)';
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
		} elseif ($name == 'numcode') {
			$type = 'SMALLINT';
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
	 * All columns from a table + meta infos
	 * 
	 * @param string $table
	 * @return array
	 */
	function listColumns($table) {
		$driver = $this->getDriver();

		switch ($driver) {
			case 'sqlite':
				$result = $this->query("PRAGMA table_info($table)");
				$infos = $result->fetchAll();
				$result = $this->query("PRAGMA index_info($table)");
				$index = $result->fetchAll();
				$result = $this->query("PRAGMA foreign_key_list($table)");
				$fks = $result->fetchAll();

				$meta = array();
				foreach ($infos as $info) {
					$data = array(
						'name' => $info['name'],
						'type' => $info['type'],
						'not_null' => $info['notnull'],
						'default' => $info['dflt_value'],
						'pk' => $info['pk'],
						'extra' => null,
						'index' => 0,
						'fk' => array()
					);
					foreach ($index as $i) {
						if ($i['name'] == $info['name']) {
							$data['index'] = 1;
						}
					}
					foreach ($fks as $fk) {
						if ($fk['from'] == $info['name']) {
							$data['fk'] = array(
								'table' => $fk['table'],
								'column' => $fk['to'],
								'on_update' => $fk['on_update'],
								'on_delete' => $fk['on_delete'],
								'match' => $fk['match']
							);
						}
					}
					$meta[] = $data;
				}
				break;
			default:
				$result = $this->query("DESCRIBE $table");
				$infos = $result->fetchAll();
				$result = $this->query("select 
    concat(table_name, '.', column_name) as 'foreign key',  
    concat(referenced_table_name, '.', referenced_column_name) as 'references'
FROM
    information_schema.key_column_usage
WHERE
    referenced_table_name is not null;");
				
				$meta = array();
				foreach ($infos as $info) {
					$data = array(
						'name' => $info['Field'],
						'type' => $info['Type'],
						'not_null' => ($info['Null'] === 'NO') ? 1 : 0,
						'default' => $info['Default'],
						'pk' => (strpos($info['Key'], 'PRI') !== false) ? 1 : 0,
						'extra' => $info['Extra'],
						'index' => (strpos($info['Key'], 'UNI') !== false) ? 1 : 0,
						'fk' => array()
					);
					$meta[] = $data;
				}
				break;
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

		$type = $this->getDriver();

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
	function listForeignKeys($table) {
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
	 * @return array 
	 */
	static function debugBarCallback() {
		$totalQueries = count(self::$queries);
		$time = self::$time;
		$firstLine = $totalQueries . ' queries in ' . sprintf('%0.6f', $time) . ' s';

		$limit = 100;
		$length = count(self::$queries);
		$queries = array($firstLine);
		for ($i = 0; $i < $limit && $i < $length; $i++) {
			$queries[] = self::$queries[$i];
			if ($i == $limit) {
				$queries[] = 'Only showing 100 first queries';
			}
		}

		return $queries;
	}

	/* sql helpers */

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
		if ($this->getDriver() == 'mssql') {
			$this->query('SET SHOWPLAN_ALL ON');
		}
		$results = $this->query('EXPLAIN ' . $sql);
		if ($this->getDriver() == 'mssql') {
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
	 * Return the highest value for given field. Id by default
	 * 
	 * @param string $table
	 * @param string $field
	 * @return int
	 */
	function max($table, $field = 'id') {
		$result = $pdo->query("SELECT MAX($field) FROM $table");
		if ($result) {
			return $result->fetchColumn();
		}
		return false;
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
	 * @return PdoStatement 
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

	/**
	 * Create the name of the foreign key
	 * 
	 * @param string $table
	 * @param string $column
	 * @param string $reference
	 * @return string
	 */
	public static function getFkName($table, $column, $reference) {
		return 'fk_' . $table . '_' . $column . '_' . preg_replace('/[^a-z]/', '', $reference);
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
	 * You can execute it, but you always get back the sql, in case you need
	 * to customize it yourself
	 * 
	 * @param string $table
	 * @param array $fields
	 * @param array $fkFields
	 * @param array $pkFields
	 * @param bool $execute
	 * @return string 
	 */
	function createTable($table, array $fields = array(), $fkFields = array(), $pkFields = array(), $execute = true) {
		if (is_string($pkFields) && !empty($pkFields)) {
			$pkFields = array($pkFields);
		}

		$fields = $this->addFieldType($fields, $pkFields);

		if (self::isReservedName($table)) {
			throw new PdoException($table . ' is a reserved name');
		}
		foreach ($fields as $field => $value) {
			if (self::isReservedName($field)) {
				throw new PdoException($field . ' is a reserved name in table ' . $table);
			}
		}

		$driver = $this->getDriver();

		$sql = 'CREATE TABLE IF NOT EXISTS ' . $table . "(\n";
		foreach ($fields as $field => $type) {
			$sql .= "\t" . $field . ' ' . $type . ",\n";
		}

		//primary key
		if (empty($pkFields) && in_array('id', array_keys($fields))) {
			$pkFields = array('id');
		}
		if (!empty($pkFields)) {
			if ($this->driver == 'sqlite' && in_array('id', $pkFields)) {
				//do nothing, since primary key is already defined in the field type
			} else {
				$pkName = 'pk_' . $table;
				$sql .= "\t" . 'CONSTRAINT ' . $pkName . ' PRIMARY KEY (' . implode(',', $pkFields) . ')' . ",\n";
			}
		}

		//foreign keys
		if ($this->getForeignKeySupport()) {
			foreach ($fkFields as $column => $reference) {
				$fkName = self::getFkName($table, $column, $reference);
				$sql .= "\t" . 'CONSTRAINT ' . $fkName . ' FOREIGN KEY (' . $column . ') REFERENCES ' . $reference . ",\n";
			}
		}

		$sql = rtrim($sql, ",\n");

		$sql .= "\n)";

		if ($execute) {
			$this->exec($sql);
		}

		return $sql;
	}

	function createTableLike($from, $to = null, $records = true) {
		if ($to === null) {
			$to = $from . '_copy';
		}
		switch ($this->getDriver()) {
			case 'mysql':
				$sql = 'CREATE TABLE ' . $to . ' LIKE ' . $from . ';
INSERT INTO ' . $to . ' SELECT * FROM ' . $from;

				break;
			case 'sqlite':
				$columns = $this->listColumns($from, false);
				echo '<pre>' . __LINE__ . "\n";
				print_r($columns);
				exit();
				$sql = 'CREATE TABLE ' . $to . '(' . $fieldsDefinition . ');
INSERT INTO ' . $to . ' SELECT ' . $fields . ' FROM ' . $from . ';';
				break;
			default :
				throw new PdoException('Not implemented');
		}
		$this->exec($sql);
	}

	function renameTable($from, $to) {
		switch ($this->getDriver()) {
			case 'mysql':
				$sql = 'RENAME TABLE ' . $from . ' TO ' . $to;
				break;
			case 'sqlite':
				$sql = 'ALTER TABLE ' . $from . ' RENAME TO ' . $to;
				break;
			default :
				throw new PdoException('Not implemented');
		}
		return $this->exec($sql);
	}

	/**
	 * Scaffold an alter table
	 * You can execute it, but you always get back the sql, in case you need
	 * to customize it yourself
	 * 
	 * @param string $table
	 * @param array $addFields
	 * @param array $removeFields
	 * @param bool $execute
	 * @return string 
	 */
	function alterTable($table, array $addFields = array(), array $removeFields = array(), $execute = true) {
		$driver = $this->getDriver();
		$addFields = $this->addFieldType($addFields);

		if ($driver == 'sqlite') {
			$allSql = '';
			//can only add columns, one by one, see @link http://www.sqlite.org/lang_altertable.html
			foreach ($addFields as $field => $type) {
				$sql = 'ALTER TABLE ' . $table;
				if (self::isReservedName($field)) {
					throw new PdoException($field . ' is a reserved name');
				}
				$sql .= " ADD COLUMN " . $field . " " . $type;
				if ($execute) {
					$this->exec($sql);
				}
				$allSql .= $sql . ";\n";
			}
			//drop need this, see @link http://www.sqlite.org/faq.html#q11
			if (!empty($removeFields)) {
				$cols = $this->listColumns($table, false);
				$fields = array_diff(array_map(function($item) {
									return $item['name'];
								}, $cols), $removeFields);
				$fieldsDefinition = '';

				foreach ($cols as $infos) {
					if (in_array($infos['name'], $removeFields)) {
						continue;
					}
					$notnull = $infos['notnull'] ? ' NOT NULL' : '';
					$pk = $infos['pk'] ? ' PRIMARY KEY' : '';
					if ($infos['name'] == 'id') {
						$pk .= ' AUTOINCREMENT'; //auto increment does not appear in the meta info :-(
					}
					$fieldsDefinition .= $infos['name'] . " " . $infos['type'] . $pk . $notnull . ",\n";
				}
				$fieldsDefinition = rtrim($fieldsDefinition, ",\n");
				$fields = implode(',', $fields);
				$sql = 'BEGIN TRANSACTION;
CREATE TEMPORARY TABLE ' . $table . '_backup(' . $fieldsDefinition . ');
INSERT INTO ' . $table . '_backup SELECT ' . $fields . ' FROM ' . $table . ';
DROP TABLE ' . $table . ';
CREATE TABLE ' . $table . '(' . $fieldsDefinition . ');
INSERT INTO ' . $table . ' SELECT ' . $fields . ' FROM ' . $table . '_backup;
DROP TABLE ' . $table . '_backup;
COMMIT;';
				$allSql .= $sql;
				if ($execute) {
					$this->exec($sql);
				}
			}
			$sql = $allSql;
		} else {
			$sql = 'ALTER TABLE ' . $table . "\n";

			foreach ($addFields as $field => $type) {
				if (self::isReservedName($field)) {
					throw PdoException($field . ' is a reserved name');
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
		}

		return $sql;
	}

	/**
	 * Add foreign keys
	 * 
	 * @param string $table
	 * @param array $keys array with key => reference
	 * @param bool $execute
	 * @return string 
	 */
	function addForeignKeys($table, array $keys, $execute = true) {
		if (empty($keys)) {
			return false;
		}
		if (!$this->getForeignKeySupport()) {
			return false;
		}
		if ($this->getDriver() == 'sqlite') {
			// you need to drop and recreate the table to alter constraints :(
			return false;
		}
		$allSql = '';
		foreach ($keys as $column => $reference) {
			$sql = 'ALTER TABLE ' . $table . "\n";
			$fkName = self::getFkName($table, $column, $reference);
			$sql .= 'ADD CONSTRAINT ' . $fkName . ' FOREIGN KEY (' . $column . ') REFERENCES ' . $reference;
			$allSql .= $sql . "\n";
			if ($execute) {
				$this->exec($sql);
			}
		}
		return $allSql;
	}

	/**
	 * Drop foreign keys, using naming convention
	 * 
	 * @param string $table
	 * @param array $keys  array with key => reference
	 * @param bool $execute
	 * @return string
	 */
	function dropForeignKeys($table, array $keys, $execute = true) {
		if (!$this->getForeignKeySupport()) {
			return false;
		}
		if ($this->getDriver() == 'sqlite') {
			throw new PdoException('drop foreign key not implemented for sqlite');
		}
		$allSql = '';
		foreach ($keys as $column => $reference) {
			$fkName = self::getFkName($name, $column, $reference);
			$sql = 'DROP FOREIGN KEY ' . $fkName;
			$allSql .= $sql . "\n";
			if ($execute) {
				$this->exec($sql);
			}
		}
		return $allSql;
	}

	/**
	 * Guess type to field definitions based on field name
	 * 
	 * @param array $fields
	 * @param array $pkFields
	 * @return array 
	 */
	function addFieldType(array $fields, array $pkFields = array()) {
		//do not type already typed fields
		foreach ($fields as $k => $v) {
			if (!is_int($k)) {
				return $fields;
			}
		}

		$fields_type = array();
		$driver = $this->getDriver();
		foreach ($fields as $field) {
			$type = $this->nameToType($field);
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
		$driver = $this->getDriver();

		$select = str_replace('SELECT ', '', $select);

		if ($driver == 'mysql') {
			$sql = 'CREATE OR REPLACE VIEW ' . $name . " AS SELECT \n";
		} else if ($driver == 'sqlite') {
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

/**
 * PDOStatement wrapper
 * Allows logging and throwing consistent exceptions
 */
class PdoStatement extends \PDOStatement {

	private function __construct($pdo) {
		//need to declare construct as private
	}

	function execute($params = array()) {
		$sql = $this->queryString;

		//nicer looking logs
		$niceSql = $sql;
		if (!empty($params)) {
			foreach ($params as $k => $v) {
				if (!is_numeric($v)) {
					$v = "'$v'";
				}
				$niceSql = preg_replace('/' . $k . '/', $v, $niceSql);
			}
		}

		try {
			$time = microtime(true);
			$result = parent::execute($params);
			$time = microtime(true) - $time;
			Pdo::logQuery($niceSql, $time);
		} catch (\PDOException $e) {
			Pdo::logQuery($niceSql);
			throw new PdoException($e);
		}

		return $result;
	}

}

/**
 * Extend the PdoException to make error code look nicer (and no stupid sqlstate stuff)
 */
class PdoException extends \PDOException {

	public function __construct(\PDOException $e) {
		//make the code/message more consistent
		if (strstr($e->getMessage(), 'SQLSTATE[')) {
			preg_match('/SQLSTATE\[(\w+)\]\: (.*)/', $e->getMessage(), $matches);
			if (!empty($matches)) {
				$this->code = $matches[1];
				$this->message = $matches[2];
			}
		}
	}

}