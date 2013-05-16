<?php

namespace k\sql;

use \Iterator;
use \Countable;
use \Exception;
use \RuntimeException;
use \InvalidArgumentException;

/**o
 * Smart query builder 
 * 
 * Inspiration :
 * @link https://github.com/lichtner/fluentdb/blob/master/FluentPDO.php
 */
class Query implements Iterator, Countable {

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
		'fetchedData' => array(),
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
	protected $joins ;

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
	 * Use sql cache or not
	 * @var bool
	 */
	protected $noCache ;

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
	 * @param PDO|Db $pdo
	 */
	public function __construct($pdo) {
		$this->reset();
		$this->setPdo($pdo);
	}

	public function getPdo() {
		return $this->pdo;
	}

	public function setPdo($pdo) {
		if (!$pdo instanceof \PDO) {
			throw new InvalidArgumentException("Db must be an instance of PDO");
		}
		$this->pdo = $pdo;
		return $this;
	}

	/**
	 * Factory for chaining in < php 5.4
	 * @param PDO $pdo
	 * @return Query
	 */
	public static function create($pdo) {
		return new static($pdo);
	}

	/**
	 * Reset all options
	 * 
	 * @return \k\sql\Query
	 */
	public function reset() {
		foreach($this->defaults as $k => $v) {
			$this->$k = $v;
		}
		return $this;
	}

	/**
	 * Use OR instand of AND when assembling clauses
	 * 
	 * @param bool $flag 
	 * @return \k\sql\Query
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
	 * @return \k\sql\Query
	 */
	public function fetchAs($itemClass = null, $collectionClass = null) {
		$this->fetchClass = $itemClass;
		$this->collectionClass = $collectionClass;
		if(is_subclass_of($itemClass, '\\k\\sql\\DataObject')) {
			$this->fields = $itemClass::getTableName() . '.*';
		}
		return $this;
	}

	/**
	 * Add distinct option
	 * 
	 * @param string|array $fields (optional) shortcut for fields()
	 * @return \k\sql\Query
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
	 * @return \k\sql\Query
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
	 * @return \k\sql\Query
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
	 * @return \k\sql\Query
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
	 * Set the fields tht should be selected as nullif(field,'') as field
	 * 
	 * @param string,array $fields
	 * @return \k\sql\Query
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
	 * Add a field
	 * 
	 * @param string $field 
	 */
	public function addField($field) {
		$this->fields[] = $field;
	}

	/**
	 * Add a where clause
	 * 
	 * @param string|array $key
	 * @param mixed $value (optional)
	 * @param string $operator (optional)
	 * @return \k\sql\Query
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

		if(is_object($value)) {
			if($value instanceof \k\sql\DataObject) {
				$value = $value->getId();
			}
			elseif(method_exists($value, 'toArray()')) {
				$value = $value->toArray();
			}
			else {
				throw new InvalidArgumentException('Can not use object as value');
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
	 * @return \k\sql\Query
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
	 * @return \k\sql\Query
	 */
	public function whereGt($key, $value) {
		return $this->where($key, $value, '>');
	}

	/**
	 * Where greater than or equal
	 * 
	 * @param string $key
	 * @param string $value
	 * @return \k\sql\Query
	 */
	public function whereGte($key, $value) {
		return $this->where($key, $value, '>=');
	}

	/**
	 * Where lower than
	 * 
	 * @param string $key
	 * @param string $value
	 * @return \k\sql\Query
	 */
	public function whereLt($key, $value) {
		return $this->where($key, $value, '<');
	}

	/**
	 * Where lower than or equal
	 * 
	 * @param string $key
	 * @param string $value
	 * @return \k\sql\Query
	 */
	public function whereLte($key, $value) {
		return $this->where($key, $value, '<=');
	}

	/**
	 * Where between
	 * 
	 * @param string $key
	 * @param array $values 
	 * @return \k\sql\Query
	 */
	public function whereBetween($key, $values) {
		return $this->where($key, $values, 'BETWEEN');
	}

	/**
	 * Where not null
	 * 
	 * @param string $key
	 * @param bool $blanks
	 * @return \k\sql\Query
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
	 * @return \k\sql\Query
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
	 * @param type $columns
	 * @return \k\sql\Query
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
	 * @return \k\sql\Query
	 */
	public function orderBy($columns) {
		if (is_array($columns)) {
			$columns = $columns[0] . ' ' . $columns[1];
		}
		$columns = $this->detectForeignKey($columns);
		$this->orderBy = $columns;
		return $this;
	}

	/**
	 * Add a group by clause
	 * 
	 * @param string $columns
	 * @return \k\sql\Query
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
	 * @return \k\sql\Query
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
	 * @return \k\sql\Query
	 */
	public function innerJoin($table, $predicate = null) {
		return $this->join($table, $predicate, 'inner');
	}

	/**
	 * Left join shortcut
	 * 
	 * @param string|array $table
	 * @param string $predicate
	 * @return \k\sql\Query
	 */
	public function leftJoin($table, $predicate = null) {
		return $this->join($table, $predicate, 'left');
	}

	/**
	 * Right join shortcut
	 * 
	 * @param string|array $table
	 * @param string $predicate
	 * @return \k\sql\Query
	 */
	public function rightJoin($table, $predicate = null) {
		return $this->join($table, $predicate, 'right');
	}

	/**
	 * Full join shortcut
	 * 
	 * @param string|array $table
	 * @param string $predicate
	 * @return \k\sql\Query
	 */
	public function fullJoin($table, $predicate = null) {
		return $this->join($table, $predicate, 'full');
	}

	/**
	 * Join clause
	 * 
	 * @param string|array $table
	 * @param string $predicate
	 * @param string $type
	 * @param boolean $force
	 * @return \k\sql\Query
	 */
	public function join($table, $predicate = null, $type = 'inner', $force = false) {
		if (empty($this->from)) {
			throw new RuntimeException('You must define a base table before joining');
		}
		$type = strtoupper($type);
		if (!in_array($type, array('INNER', 'LEFT', 'RIGHT', 'FULL'))) {
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
	function build() {
		if (empty($this->from)) {
			throw new Exception('You must set a table before building the statement');
		}

		$db = $this->getPdo();
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
			if (is_array($fields)) {
				$fields = implode(',', $fields);
			}
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
	 * @return PdoStatement
	 */
	function query() {
		$db = $this->getPdo();

		$sql = $this->build();

//		$results = $db->query($sql);
//		return $results;

		$stmt = $db->prepare($sql);
		$stmt->execute($this->params);
		return $stmt;
	}

	/**
	 * Allow to set in advance the fetch mode or actually do it
	 * @param string $fetchMode
	 * @param array $args
	 * @return \\k\sql\Query
	 */
	function get($fetchMode = null, $args = array()) {
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
	function fetchAll($fetchType = null, $fetchArgument = null) {
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
				$this->fetchedData = $results->fetchAll($fetchType, $fetchArgument,array(0 => $this->getPdo()));
			} else {
				$this->fetchedData = $results->fetchAll($fetchType);
			}
			$results->closeCursor();
			$results = null;
		} else {
			$this->fetchedData = array();
		}
		return $this->fetchedData;
	}

	/**
	 * Fetch only the first value
	 * 
	 * @param string $field (optional) shortcut for fields
	 * @return string
	 */
	function fetchValue($field = null) {
		if ($field !== null) {
			$this->fields = $field;
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
	function fetchColumn($field = 0) {
		if ($field) {
			$field = $this->removeTableOrAlias($field);
			$this->fields = $field;
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
	 * Fetch two columns as associative array. Ideal for drodbwns.
	 * 
	 * @param string $value
	 * @param string $key
	 * @return array
	 */
	function fetchMap($value = 'name', $key = 'id') {
		$this->fields = $key . ',' . $value;
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
	function fetchOne() {
		$this->limit = 1;
		return $this->fetch();
	}

	/**
	 * Fetch only one record
	 * 
	 * @return Orm
	 */
	function fetchOnlyOne() {
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
	function fetch($fetchType = null, $fetchArgument = null) {
		$results = $this->query();
		if ($this->fetchClass && $fetchType === null) {
			$results->setFetchMode(PDO::FETCH_CLASS, $this->fetchClass,array(0 => $this->getPdo()));
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

	function __toString() {
		return $this->build();
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
		if (strpos($key, '.') !== false) {
			$key_parts = explode('.', $key);

			//do not create joins when they are already there
			$table = $key_parts[0];
			if (isset($this->aliases[$table])) {
				$table = $this->aliases[$table];
			}
			foreach ($this->joins as $join) {
				if ($join['table'] === $table) {
					return $key;
				}
			}
			if ($table == $this->from) {
				return $key;
			}
			$this->leftJoin($table);
			if (empty($this->fields)) {
				$this->fields = array($this->from . '.*');
			}
			$this->addField($table . '.' . $key_parts[1]);
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
		if (empty($this->fetchedData)) {
			$this->fetchedData = $this->fetchAll();
		}
		return isset($this->fetchedData[$this->position]);
	}

	public function count() {
		if (empty($this->fetchedData)) {
			$this->fetchedData = $this->fetchAll();
		}
		return count($this->fetchedData);
	}

}