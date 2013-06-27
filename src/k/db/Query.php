<?php

namespace k\db;

use \Iterator;
use \ArrayAccess;
use \Countable;
use \Exception;
use \RuntimeException;
use \InvalidArgumentException;

/**
 * Smart query builder 
 * 
 * - Pseudo full text search (for inno db)
 * - Integration with ORM (auto joins and prefetch)
 * 
 * Inspiration :
 * @link https://github.com/lichtner/fluentdb/blob/master/FluentPDO.php
 * @link https://github.com/monochromegane/QueryBuilder
 */
class Query implements Iterator, ArrayAccess, Countable {

	const FULL_TEXT_MAX_TOKENS = 6;
	const FULL_TEXT_MAX_LENGTH = 255;
	const JOIN_LEFT = 'LEFT';
	const JOIN_RIGHT = 'RIGHT';
	const JOIN_FULL = 'FULL';
	const JOIN_INNER = 'INNER';
	
	/**
	 * Store defaults for reset
	 * @var array
	 */
	protected $defaults = array(
		'from' => null,
		'aliases' => array(),
		'where' => array(),
		'having' => array(),
		'joins' => array(),
		'limit' => null,
		'orderBy' => array(),
		'groupBy' => array(),
		'fields' => array(),
		'emptyOrNull' => array(),
		'distinct' => false,
		'or' => false,
		'fetchClass' => null,
		'collectionClass' => null,
		'noCache' => false,
		'position' => 0,
		'fetchedData' => null,
		'params' => array(),
		'fetchMode' => 'fetchAll',
		'fetchArgs' => array()
	);

	/**
	 * Pdo instance
	 * @var PDO
	 */
	protected $pdo;

	/**
	 * From table
	 * @var string
	 */
	protected $from;

	/**
	 * Store aliases as alias => table
	 * @var array 
	 */
	protected $aliases;

	/**
	 * Where clauses
	 * @var array
	 */
	protected $where;

	/**
	 * Having clauses
	 * @var array
	 */
	protected $having;

	/**
	 * Joins like "type" => , "table" =>, "predicate" =>
	 * @var array 
	 */
	protected $joins;

	/**
	 * Limit clause
	 * @var string
	 */
	protected $limit;

	/**
	 * Order by clauses
	 * @var array
	 */
	protected $orderBy;

	/**
	 * Group by clauses
	 * @var array
	 */
	protected $groupBy;

	/**
	 * Field selection
	 * @var array
	 */
	protected $fields;

	/**
	 * Empty or null fields
	 * @var array
	 */
	protected $emptyOrNull;

	/**
	 * Is distinct
	 * @var bool
	 */
	protected $distinct;

	/**
	 * Custom options
	 * @var string
	 */
	protected $options;

	/**
	 * Should we add clauses or use or
	 * @var bool
	 */
	protected $or;

	/**
	 * @var array
	 */
	protected $fetchArgs;

	/*
	 * Fetch mode for get
	 * @var string
	 */
	protected $fetchMode;

	/**
	 * Fetch as class
	 * @var string
	 */
	protected $fetchClass;
	
	/**
	 * Fetch as object
	 * @var ArrayObject
	 */
	protected $collectionClass;

	/** 	
	 * Use sql cache or not
	 * @var bool
	 */
	protected $noCache;

	/**
	 * Position (iterator)
	 * @var int 
	 */
	protected $position;

	/**
	 * Fetched data (iterator)
	 * @var array
	 */
	protected $fetchedData;

	/**
	 * Params for prepared statement
	 * @var array
	 */
	protected $params;

	/**
	 * A file or a psr 3 log
	 * @var string|object
	 */
	protected $log;

	/**
	 * Create a new Query object and allow passing directly the from
	 * 
	 * @param PDO $pdo
	 */
	public function __construct($pdo) {
		$this->reset();
		$this->setPdo($pdo);
	}

	/**
	 * @return PDO
	 */
	public function getPdo() {
		return $this->pdo;
	}

	/**
	 * @param PDO $pdo
	 * @return \k\db\Query
	 * @throws InvalidArgumentException
	 */
	public function setPdo($pdo) {
		if (!$pdo instanceof \PDO) {
			throw new InvalidArgumentException("You must pass an instance of PDO");
		}
		$this->pdo = $pdo;
		return $this;
	}

	/**
	 * Reset all options
	 * 
	 * @return \k\db\Query
	 */
	public function reset() {
		foreach ($this->defaults as $k => $v) {
			$this->$k = $v;
		}
		return $this;
	}

	/**
	 * Use OR instand of AND when assembling clauses
	 * 
	 * @param bool $flag 
	 * @return \k\db\Query
	 */
	public function useOr($flag = true) {
		$this->or = $flag;
		return $this;
	}

	/**
	 * Fetch as class
	 * 
	 * @param string $itemClass 
	 * @param string $collectionClass 
	 * @return \k\db\Query
	 */
	public function fetchAs($itemClass = null, $collectionClass = null) {
		$this->fetchClass = $itemClass;
		$this->collectionClass = $collectionClass;
		
		//if we have an orm model, fetch only the table fields by default
		if (is_subclass_of($itemClass, '\\k\\db\\Orm')) {
			$table = $itemClass::getTableName();
			$this->fields($table . '.*');
		}
		return $this;
	}

	/**
	 * Add distinct option
	 * 
	 * @param string|array $fields (optional) shortcut for fields()
	 * @return \k\db\Query
	 */
	public function distinct($fields = null) {
		if ($fields !== null) {
			$this->fields($fields);
		}
		$this->distinct = true;
		return $this;
	}

	/**
	 * Disable cache
	 * 
	 * @return \k\db\Query
	 */
	public function noCache() {
		$this->noCache = true;
		return $this;
	}

	/**
	 * Select from table
	 * 
	 * @param string|array $table
	 * @param string $alias
	 * @return \k\db\Query
	 */
	public function from($table, $alias = null) {
		if (is_array($table)) {
			$table = $table[0];
			$alias = $table[1];
		}
		if ($alias !== null) {
			if (!in_array($alias, array_values($this->aliases))) {
				$this->aliases[$alias] = $table;
			}
		}
		$this->from = $table;
		return $this;
	}

	/**
	 * Specify fields
	 * 
	 * @param string|array $fields 
	 * @return \k\db\Query
	 */
	public function fields($fields) {
		if (!is_array($fields)) {
			$fields = explode(',', $fields);
			array_walk($fields, 'trim');
		}
		$this->fields = $fields;
		return $this;
	}
	
	/**
	 * Add a field
	 * 
	 * @param string $field 
	 */
	public function addField($field) {
		if(is_array($field)) {
			foreach($field as $f) {
				$this->addField($f);
			}
		}
		$this->fields[] = $field;
	}

	/**
	 * Set the fields tht should be selected as nullif(field,'') as field
	 * 
	 * @param string,array $fields
	 * @return \k\db\Query
	 */
	public function emptyOrNull($fields) {
		if (!is_array($fields)) {
			$fields = explode(',', $fields);
			array_walk($fields, 'trim');
		}
		$this->emptyOrNull = $fields;
		return $this;
	}

	/**
	 * Add a where clause
	 * 
	 * Sample usage
	 * 
	 * ('field','value')
	 * ([
	 *	'field' => 'value',
	 *	'otherfield' => 'othervalue')
	 * ])
	 * ('id',[1,2,3])
	 * ('total',500,'>');
	 * 
	 * @param string|array $key Pass null to reset where clause
	 * @param mixed $value (optional)
	 * @param string $operator (optional)
	 * @return \k\db\Query
	 */
	public function where($key = null, $value = '', $operator = null) {
		//pass null to reset where
		if ($key === null) {
			$this->where = array();
			return $this;
		}
		if (empty($key)) {
			return $this;
		}
		$db = $this->getPdo();

		if (is_object($value)) {
			if ($value instanceof \k\db\Orm) {
				$value = $value->getId();
			} elseif (method_exists($value, 'toArray()')) {
				$value = $value->toArray();
			} else {
				throw new InvalidArgumentException('Can not use object of class "'.get_class($value).'" as value');
			}
		}

		if ($value === '') {
			if (is_array($key)) {
				//simple filter
				foreach ($key as $k => $v) {
					$this->where($k, $v);
				}
				return $this;
			} else {
				//custom sql
				$this->where[] = $key;
			}
			return $this;
		}

		$key = $this->detectForeignKey($key);

		//placeholders
		if (strpos($key, '?') !== false) {
			$key = str_replace('?', $this->replaceByPlaceholder($value), $key);
			$this->where[] = $key;
			return $this;
		}
		if (!$operator) {
			if ($value === null) {
				$this->where[] = $key . ' IS NULL';
			} elseif (is_array($value)) {
				$this->where[] = $key . ' IN (' . $db->quote($value) . ')';
			} elseif (strpos($value, '%') !== false) {
				$this->where[] = $key . ' LIKE ' . $this->replaceByPlaceholder($value);
			} else {
				$this->where[] = $key . ' = ' . $this->replaceByPlaceholder($value);
			}
		} else {
			if ($operator == 'BETWEEN') {
				$this->where[] = $key . ' BETWEEN ' . $db->quote($value[0]) . ' AND ' . $db->quote($value[1]);
			} else {
				if (strpos($operator, 'IN') !== false) {
					$this->where[] = $key . ' ' . $operator . ' (' . $db->quote($value) . ')';
				} else {
					$this->where[] = $key . ' ' . $operator . ' ' . $this->replaceByPlaceholder($value);
				}
			}
		}
		return $this;
	}

	/**
	 * Apply filter with a value
	 * @param array|string $filter
	 * @param mixed $value
	 */
	public function filter($filter, $value = null) {
		if (is_array($filter)) {
			foreach ($filter as $f => $v) {
				$this->filter($f, $v);
			}
			return;
		}
		if (!$value) {
			return;
		}
		$operator = null;
		if(is_string($value)) {
			$pattern = '/^([><=])*/';
			if(preg_match($pattern, $value,$matches)) {
				if(!empty($matches)) {
					$operator = $matches[0];
					$value = preg_replace($pattern, '', $value);
				}
			}
		}
		$this->where($filter, $value, $operator);
	}

	public function fullTextSearch($column, $text) {
		// Multi-column
		if(!is_array($column)) {
			$column = explode('|', $column);
		}
		foreach($column as $col) {
			$this->detectForeignKey($col);
		}
		$columns = array_map(function($value) { return "{$value}"; }, $column);
		$column = "replace(concat_ws(' ', ".implode(',', $columns)."), ' ', '')";

		// Slugify and tokenize
		$text = strtolower($text);
		$text = iconv('utf-8', 'us-ascii//translit', $text);

		$text = preg_replace(':[^a-z0-9]+:', ' ', $text);
		$text = preg_replace(':([a-z])([0-9]):', '$1 $2', $text);
		$text = preg_replace(':([0-9])([a-z]):', '$1 $2', $text);

		$text = preg_replace(':(^|[^0-9])0+:', '$1', $text);
		$tokens = preg_split(':\\s+:', $text);

		// Token limits
		if (! count($tokens)) {
			return;
		}
		if (count($tokens) > self::FULL_TEXT_MAX_TOKENS) {
			$tokens = array_slice($tokens, 0, self::FULL_TEXT_MAX_TOKENS);
		}
		// Filter length
		$length = 0;
		$maxLength = self::FULL_TEXT_MAX_LENGTH;
		$tokens = array_filter($tokens, function($token) use (& $length, $maxLength) {
			return ($length += strlen($token)) <= $maxLength;
		});
		
		$text = '%'.implode('%', $tokens).'%';
		$param = $this->replaceByPlaceholder($text);
		$this->where[] = sprintf("concat(%s) like {$param}",
			implode(',', array_fill(0, count($tokens), $column))
		);
		return $this;
	}
	
	/**
	 * Replace value by placeholder
	 * @param type $value
	 * @return string
	 */
	protected function replaceByPlaceholder($value) {
		$p = ':p' . count($this->params);
		$this->params[$p] = $value;
		return $p;
	}

	/**
	 * Where not
	 * 
	 * @param string $key
	 * @param string $value
	 * @return \k\db\Query
	 */
	public function whereNot($key, $value) {
		if (is_array($value)) {
			return $this->where($key, $value, 'NOT IN');
		}
		return $this->where($key, $value, '!=');
	}

	/**
	 * Where greater than
	 * 
	 * @param string $key
	 * @param string $value
	 * @return \k\db\Query
	 */
	public function whereGt($key, $value) {
		return $this->where($key, $value, '>');
	}

	/**
	 * Where greater than or equal
	 * 
	 * @param string $key
	 * @param string $value
	 * @return \k\db\Query
	 */
	public function whereGte($key, $value) {
		return $this->where($key, $value, '>=');
	}

	/**
	 * Where lower than
	 * 
	 * @param string $key
	 * @param string $value
	 * @return \k\db\Query
	 */
	public function whereLt($key, $value) {
		return $this->where($key, $value, '<');
	}

	/**
	 * Where lower than or equal
	 * 
	 * @param string $key
	 * @param string $value
	 * @return \k\db\Query
	 */
	public function whereLte($key, $value) {
		return $this->where($key, $value, '<=');
	}

	/**
	 * Where between
	 * 
	 * @param string $key
	 * @param array $values 
	 * @return \k\db\Query
	 */
	public function whereBetween($key, $values) {
		return $this->where($key, $values, 'BETWEEN');
	}

	/**
	 * Where not null
	 * 
	 * @param string $key
	 * @param bool $blanks
	 * @return \k\db\Query
	 */
	public function whereNotNull($key, $blanks = true) {
		$where = $key . ' IS NOT NULL';
		if ($blanks) {
			$where .= ' OR ' . $key . " != ''";
		}
		return $this->where($where);
	}

	/**
	 * Where null
	 * 
	 * @param string $key
	 * @param bool $blanks
	 * @return \k\db\Query
	 */
	public function whereNull($key, $blanks = true) {
		$where = $key . ' IS NULL';
		if ($blanks) {
			$where .= ' OR ' . $key . " = ''";
		}
		return $this->where($where);
	}

	/**
	 * Add a having clause
	 * 
	 * @param string|array $columns
	 * @return \k\db\Query
	 */
	public function having($columns) {
		if ($columns === null) {
			$this->having = array();
			return $this;
		}
		$columns = $this->detectForeignKey($columns);
		return $this;
	}

	/**
	 * Add an order by clause
	 * 
	 * @param type $columns
	 * @return \k\db\Query
	 */
	public function orderBy($columns) {
		if (is_array($columns)) {
			$cols = [];
			foreach ($columns as $k => $v) {
				$cols .= $k . ' ' . $v;
			}
			$columns = implode(',', $cols);
		}
		$columns = trim($columns);
		if (empty($columns)) {
			return $this;
		}
		$columns = $this->detectForeignKey($columns);
		$this->orderBy = $columns;
		return $this;
	}

	/**
	 * Add a group by clause
	 * 
	 * @param string $columns
	 * @return \k\db\Query
	 */
	public function groupBy($columns) {
		if ($columns === null) {
			$this->groupBy = array();
			return $this;
		}
		$this->groupBy[] = $columns;
		return $this;
	}

	/**
	 * Limit clause
	 * 
	 * @param type $value
	 * @return \k\db\Query
	 */
	public function limit($value) {
		if (is_array($value)) {
			$value = $value[0] . ' ' . $value[1];
		}
		$this->limit = $value;
		return $this;
	}

	/**
	 * Inner join shortcut
	 * 
	 * @param string|array $table
	 * @param string $predicate
	 * @return \k\db\Query
	 */
	public function innerJoin($table, $predicate = null) {
		return $this->join($table, $predicate, self::JOIN_INNER);
	}

	/**
	 * Left join shortcut
	 * 
	 * @param string|array $table
	 * @param string $predicate
	 * @return \k\db\Query
	 */
	public function leftJoin($table, $predicate = null) {
		return $this->join($table, $predicate, self::JOIN_LEFT);
	}

	/**
	 * Right join shortcut
	 * 
	 * @param string|array $table
	 * @param string $predicate
	 * @return \k\db\Query
	 */
	public function rightJoin($table, $predicate = null) {
		return $this->join($table, $predicate, self::JOIN_RIGHT);
	}

	/**
	 * Full join shortcut
	 * 
	 * @param string|array $table
	 * @param string $predicate
	 * @return \k\db\Query
	 */
	public function fullJoin($table, $predicate = null) {
		return $this->join($table, $predicate, self::JOIN_FULL);
	}

	/**
	 * Join clause
	 * 
	 * @param string|array $table
	 * @param string $predicate
	 * @param string $type
	 * @param boolean $force
	 * @return \k\db\Query
	 */
	public function join($table, $predicate = null, $type = 'inner', $force = false) {
		if (empty($this->from)) {
			throw new RuntimeException('You must define a base table before joining');
		}
		$type = strtoupper($type);
		if (!in_array($type, array(self::JOIN_FULL, self::JOIN_INNER, self::JOIN_LEFT, self::JOIN_RIGHT))) {
			throw new InvalidArgumentException('Unsupported join type : ' . $type);
		}

		$alias = '';
		//if we pass an table,alias combo
		if (is_array($table)) {
			$alias = $table[1];
			$table = $table[0];
		}
		//if we pass only an alias (mainly used in detect key method)
		if (isset($this->aliases[$table])) {
			$table = $this->aliases[$table];
		}
		//look for an alias
		if (empty($alias)) {
			foreach ($this->aliases as $keyAlias => $valueTable) {
				if ($valueTable == $table) {
					$alias = $keyAlias;
				}
			}
		}
		$tableOrAlias = $table;
		if (!empty($alias)) {
			$tableOrAlias = $alias;
		}
		if (!$force) {
			foreach ($this->joins as $join) {
				if ($join['table'] == $table) {
					return;
				}
			}
		}

		//store alias
		if (!isset($this->aliases[$alias])) {
			$this->aliases[$alias] = $table;
		}
		//autogenerate
		if ($predicate === null) {
			$pk = $foreignPk = 'id';
			$pk = $table . '_' . $pk;
			$predicate = $this->tableOrAlias() . '.' . $pk . ' = ' . $tableOrAlias . '.' . $foreignPk;
		}

		if (strpos($predicate, '=') !== false) {
			$predicate = 'ON ' . $predicate;
		} else {
			$predicate = 'USING ' . $predicate;
		}
		$tableAs = $table;
		$this->joins[] = array('type' => $type, 'table' => $table, 'predicate' => $predicate);
		return $this;
	}

	/**
	 * Build the query
	 * 
	 * @return string
	 */
	public function build() {
		if (empty($this->from)) {
			throw new Exception('You must set a table before building the statement');
		}

		$tableAs = $this->from;
		$alias = $this->getAlias($tableAs);
		if ($alias) {
			$tableAs .= ' AS ' . $alias;
		}

		//select
		$whereJoin = ' AND ';
		if ($this->or) {
			$whereJoin = ' OR ';
		}

		$options = $this->options;
		if (strpos($options, 'DISTINCT') === false && $this->distinct) {
			$options .= ' DISTINCT';
		}

		$sql = 'SELECT';
		if (!empty($options)) {
			$sql .= ' ' . trim($options);
		}
		if ($this->noCache) {
			$sql .= ' SQL_NO_CACHE';
		}

		//fields
		$fields = $this->fields;
		if (empty($fields)) {
			$fields = '*';
		} else {
			$fields = implode(',', $fields);
		}
		foreach ($this->emptyOrNull as $k => $v) {
			$fields = str_replace($k, "nullif($k,'') AS $k", $fields);
		}

		$sql .= ' ' . $fields . ' FROM ' . $tableAs;

		if (!empty($this->joins)) {
			foreach ($this->joins as $join) {
				$alias = $this->getAlias($join['table']);
				$table = $join['table'];
				if ($alias) {
					$table .= ' AS ' . $alias;
				}
				$sql .= ' ' . $join['type'] . ' JOIN ' . $table . ' ' . $join['predicate'];
			}
		}
		if (!empty($this->where)) {
			$sql .= ' WHERE (' . implode(')' . $whereJoin . '(', $this->where) . ')';
		}
		if (!empty($this->groupBy)) {
			$group_by = $this->groupBy;
			if (is_array($group_by)) {
				$group_by = implode(',', $group_by);
			}
			$sql .= 'GROUP BY ' . $group_by;
		}
		if (!empty($this->having)) {
			$sql .= ' HAVING ' . implode($whereJoin, $this->having);
		}
		if (!empty($this->orderBy)) {
			$order_by = $this->orderBy;
			if (is_array($this->orderBy)) {
				$order_by = implode(',', $this->orderBy);
			}
			$sql .= ' ORDER BY ' . $order_by;
		}
		if (!empty($this->limit)) {
			$sql .= ' LIMIT ' . $this->limit;
		}

		return $sql;
	}

	/**
	 * Do the query
	 * 
	 * @return \k\db\PdoSTatement
	 */
	public function query() {
		$pdo = $this->getPdo();
		$sql = $this->build();

//		$results = $db->query($sql);
//		return $results;

		$stmt = $pdo->prepare($sql);
		$stmt->execute($this->params);
		return $stmt;
	}

	/**
	 * Allow to set in advance the fetch mode or actually do it
	 * @param string $fetchMode You can use class constants
	 * @param array $args
	 * @return \k\db\Query
	 */
	public function get($fetchMode = null, $args = array()) {
		if ($fetchMode === null) {
			return call_user_func_array(array($this, $this->fetchMode), $this->fetchArgs);
		}
		$this->fetchMode = $fetchMode;
		$this->fetchArgs = $args;
		return $this;
	}

	/**
	 * Query and fetch all
	 * 
	 * @param int $fetchType
	 * @param mixed $fetchArgument
	 * @return array 
	 */
	public function fetchAll($fetchType = null, $fetchArgument = null) {
		if ($this->fetchClass && $fetchType === null) {
			$fetchType = PDO::FETCH_CLASS;
			$fetchArgument = $this->fetchClass;
		}
		if ($fetchType === null) {
			$fetchType = PDO::FETCH_ASSOC;
		}
		$results = $this->query();
		if ($results) {
			if ($fetchArgument) {
				$this->fetchedData = $results->fetchAll($fetchType, $fetchArgument, array($this));
			} else {
				$this->fetchedData = $results->fetchAll($fetchType);
			}
			$results->closeCursor();
			$results = null;
		} else {
			$this->fetchedData = array();
		}
		if($this->collectionClass) {
			$class = $this->collectionClass;
			$this->fetchedData = new $class($this->fetchedData);
		}
		return $this->fetchedData;
	}

	/**
	 * Fetch all records index by column
	 * 
	 * @param string $column
	 * @param int $fetchType
	 * @param mixed $fetchArgument
	 * @return array
	 */
	public function fetchBy($column = 'id', $fetchType = null, $fetchArgument = null) {
		$data = $this->fetchAll($fetchType, $fetchArgument);
		$dataBy = array();
		foreach ($data as $row) {
			if (is_object($row)) {
				$dataBy[$row->$column] = $row;
			} else {
				$dataBy[$row[$column]] = $row;
			}
		}
		return $dataBy;
	}

	/**
	 * Fetch only the first value
	 * 
	 * @param string $field (optional) shortcut for fields
	 * @return string
	 */
	public function fetchValue($field = null) {
		if ($field !== null) {
			$this->fields($field);
		}
		$row = $this->fetch(PDO::FETCH_NUM);
		if (isset($row[0])) {
			return $row[0];
		}
		return null;
	}

	/**
	 * Fetch the column as array. Ideal for making list.
	 * 
	 * @param string $field
	 * @return array
	 */
	public function fetchColumn($field = 0) {
		if ($field) {
			$field = $this->removeTableOrAlias($field);
			$this->fields($field);
		}

		$fetch = PDO::FETCH_ASSOC;
		if (is_int($field)) {
			$fetch = PDO::FETCH_NUM;
		}
		$rows = $this->fetchAll($fetch);
		$res = array();
		foreach ($rows as $row) {
			$res[] = $row[$field];
		}
		return $res;
	}

	/**
	 * Fetch two columns as associative array. Ideal for dropdowns.
	 * 
	 * @param string $value
	 * @param string $key
	 * @return array
	 */
	public function fetchMap($value = 'name', $key = 'id') {
		$this->fields($key . ',' . $value);
		$rows = $this->fetchAll(PDO::FETCH_OBJ);
		$res = array();

		$key = $this->removeTableOrAlias($key);
		$value = $this->removeTableOrAlias($value);

		foreach ($rows as $row) {
			$res[$row->$key] = $row->$value;
		}
		return $res;
	}

	/**
	 * Fetch one record (limit)
	 * 
	 * @return array|object
	 */
	public function fetchOne() {
		$this->limit = 1;
		return $this->fetch();
	}

	/**
	 * Fetch only one record
	 * 
	 * @return \k\db\Orm
	 */
	public function fetchOnlyOne() {
		$results = $this->fetchAll();
		if (count($results) == 1) {
			return $results[0];
		}
		return false;
	}

	/**
	 * Query and fetch
	 * 
	 * @return array|object
	 */
	public function fetch($fetchType = null, $fetchArgument = null) {
		$results = $this->query();
		if ($this->fetchClass && $fetchType === null) {
			$results->setFetchMode(PDO::FETCH_CLASS, $this->fetchClass);
		}
		if ($fetchType === null) {
			$fetchType = PDO::FETCH_ASSOC;
		}
		if ($results) {
			$fetch = $results->fetch();
			$results->closeCursor();
			$results = null;
			return $fetch;
		}
		return false;
	}

	public function __toString() {
		return $this->getPdo()->formatQuery($this->build());
	}

	/* helpers */

	/**
	 * Current table or alias
	 * @return string
	 */
	protected function tableOrAlias() {
		$alias = '';
		$table = $this->from;
		foreach ($this->aliases as $keyAlias => $valueTable) {
			if ($valueTable == $table) {
				$alias = $keyAlias;
			}
		}
		if (!empty($alias)) {
			return $alias;
		}
		return $table;
	}

	/**
	 * Useful to reuse the field as array key afterwards
	 * 
	 * @param string $field
	 * @return string
	 */
	protected function removeTableOrAlias($field) {
		return preg_replace('/(\w*\.)?(\w*)( AS \w*)?/i', "$2", $field);
	}

	/**
	 * Allows smart joins by detecting tables in field names
	 * 
	 * @param string $key
	 * @return string 
	 */
	protected function detectForeignKey($key) {
		preg_match_all('/[a-zA-Z0-9_]*\.[a-zA-Z0-9_]*/', $key, $matches);
		if (!empty($matches[0])) {
			foreach ($matches[0] as $match) {
				$parts = explode('.', $match);
				$table = $parts[0];

				//can use relation name instead of table
				if ($this->fetchClass && is_subclass_of($this->fetchClass, '\\k\\db\\Orm')) {
					$class = $this->fetchClass;
					$relations = $class::getHasOneRelations();
					if (isset($relations[$table])) {
						$newClass = $relations[$table];
						$table = $newClass::getTableName();
					}
				}

				//if we use an alias
				if (isset($this->aliases[$table])) {
					$key = str_replace($table . '.', $this->aliases[$table], $key);
					$table = $this->aliases[$table];
				}
				
				//do not create joins when they are already there
				foreach ($this->joins as $join) {
					if ($join['table'] === $table) {
						continue;
					}
				}
				if ($table == $this->from) {
					return $key;
				}
				
				//auto join
				$this->leftJoin($table);
				if (empty($this->fields)) {
					$this->fields($this->from . '.*');
				}
				$this->addField($table . '.' . $parts[1]);
				$newValue = $table . '.' . $parts[1];
				$key = str_replace($match, $newValue, $key);
			}
		}
		return $key;
	}

	/**
	 * Get an alias for a table
	 * 
	 * @param string $table
	 * @return string
	 */
	protected function getAlias($table) {
		foreach ($this->aliases as $alias => $alias_table) {
			if ($alias_table == $table) {
				return $alias;
			}
		}
		return false;
	}

	/**
	 * Prefetch records for orm
	 * 
	 * @param string $relation
	 * @param string $injectedClass
	 * @return boolean
	 * @throws Exception
	 */
	public function prefetch($relation = null, $injectedClass = null) {
		if ($injectedClass) {
			return $injectedClass::inject($this->fetchedData, $relation);
		}
		$row = $this->fetchedData[0];
		if (!$row instanceof Orm) {
			throw new Exception('Prefetch only works for records of class Orm');
		}
		$class = get_class($row);
		$rels = $class::getHasOneRelations();
		if ($relation) {
			$injectedClass = $rels[$relation];
		}
		if ($injectedClass) {
			return $injectedClass::inject($this->fetchedData, $relation);
		}
		foreach ($rels as $relation => $injectedClass) {
			$injectedClass::inject($this->fetchedData, $relation);
		}
		return true;
	}

	/**
	 * @return array
	 */
	public function getFetchedData() {
		if ($this->fetchedData === null) {
			$this->fetchedData = $this->fetchAll();
		}
		return $this->fetchedData;
	}

	/* --- iterator --- */

	public function current() {
		return $this->fetchedData[$this->position];
	}

	public function key() {
		return $this->position;
	}

	public function next() {
		++$this->position;
	}

	public function rewind() {
		$this->position = 0;
	}

	public function valid() {
		if ($this->fetchedData === null) {
			$this->fetchedData = $this->fetchAll();
		}
		return isset($this->fetchedData[$this->position]);
	}

	/* --- countable --- */

	public function quickcount($query = 'count(*)') {
		$original = $this->fields;
		$this->fields = array($query);
		$res = $this->query();
		$this->fields = $original;
		return $res->fetchColumn();
	}

	public function count() {
		if ($this->fetchedData === null) {
			$this->fetchedData = $this->fetchAll();
		}
		return count($this->fetchedData);
	}

	/* --- arrayaccess --- */

	public function offsetSet($offset, $value) {
		if ($this->fetchedData === null) {
			$this->fetchedData = $this->fetchAll();
		}
		if (is_null($offset)) {
			$this->fetchedData[] = $value;
		} else {
			$this->fetchedData[$offset] = $value;
		}
	}

	public function offsetExists($offset) {
		if ($this->fetchedData === null) {
			$this->fetchedData = $this->fetchAll();
		}
		return isset($this->fetchedData[$offset]);
	}

	public function offsetUnset($offset) {
		unset($this->fetchedData[$offset]);
	}

	public function offsetGet($offset) {
		if ($this->offsetExists($offset)) {
			return $this->fetchedData[$offset];
		}
	}

}