<?php

namespace k\db;

use \InvalidArgumentException;
use \BadMethodCallException;
use \stdClass;

/**
 * Table class
 * 
 * Table information is done through introspection or by asking Orms if
 * they exists
 *
 * select/insert/update/delete are fully hookable by adding definitions in Orms
 * 
 * @author lekoala
 */
class Table {

	protected $name;
	protected $pdo;
	protected $itemClass = '*';
	protected $collectionClass = '*';
	public static $classPrefix = 'Model_';
	public static $collectionSuffix = 'Collection';

	public function __construct($name, $pdo) {
		$this->setName($name);
		$this->setPdo($pdo);
	}
	
	/**
	 * Get a new instance of a class
	 * @return \stdClass|\k\db\Orm
	 */
	public function getNewInstance() {
		$class = $this->getItemClass();
		if($class) {
			return new $class($this->getPdo());
		}
		return new stdClass();
	}

	public function getPrimaryKey() {
		$class = $this->getItemClass();
		if ($class && is_subclass_of($class, '\\k\\db\\Orm')) {
			return $class::getPrimaryKey();
		}
		//TODO : introspect db
	}

	public function getPrimaryKeys() {
		$class = $this->getItemClass();
		if ($class && is_subclass_of($class, '\\k\\db\\Orm')) {
			return $class::getPrimaryKeys();
		}
		//TODO : introspect db
	}

	public function getNameAsClass() {
		return str_replace(' ', '', ucwords(preg_replace('/[^A-Z^a-z^0-9]+/', ' ', $this->getName())));
	}

	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		$this->name = $name;
		return $this;
	}

	public function getPdo() {
		return $this->pdo;
	}

	public function setPdo($pdo) {
		if (!$pdo instanceof \PDO) {
			throw new InvalidArgumentException('Must pass an instance of PDO');
		}
		$this->pdo = $pdo;
		return $this;
	}

	public function getItemClass() {
		if ($this->itemClass === '*') {
			$this->itemClass = self::$classPrefix . $this->getNameAsClass();
			if (!class_exists($this->itemClass)) {
				$this->itemClass = null;
			}
		}
		return $this->itemClass;
	}

	public function setItemClass($itemClass) {
		$this->itemClass = $itemClass;
		return $this;
	}

	public function getCollectionClass() {
		if ($this->collectionClass === '*') {
			$this->collectionClass = self::$classPrefix . $this->getNameAsClass() . self::$collectionSuffix;
			if (!class_exists($this->collectionClass)) {
				$this->collectionClass = null;
			}
		}
		return $this->collectionClass;
	}

	public function getClassPrefix() {
		return self::$classPrefix;
	}

	public function setClassPrefix($classPrefix) {
		self::$classPrefix = $classPrefix;
		return $this;
	}

	public function getCollectionSuffix() {
		return self::$collectionSuffix;
	}

	public function setCollectionSuffix($collectionSuffix) {
		self::$collectionSuffix = $collectionSuffix;
		return $this;
	}

	/**
	 * Shortcut to get the query builder
	 * @return \k\db\Query;
	 */
	public function q() {
		return $this->query();
	}

	/**
	 * Get a query builder for this table
	 * 
	 * @param bool $class
	 * @return \k\db\Query;
	 */
	public function query($class = true) {
		$q = new Query($this->getPdo());
		$q->from($this->getName())->fields($this->getName() . '.*');
		if ($class && $this->getItemClass()) {
			$q->fetchAs($this->getItemClass());
		}
		return $q;
	}
	
	/**
	 * Get the fluent query builder with class defaults
	 * @return \k\db\Query;
	 */
	public function defaultQuery() {
		$q = $this->query();
		$q->where(static::defaultWhere());
		$q->orderBy(static::defaultOrderBy());
		return $q;
	}
	
	/**
	 * @param array $data
	 * @return int The id of the record
	 */
	public function insert($data = array()) {
		return $this->getPdo()->insert($this->getName(), $data);
	}

	/**
	 * @param array $data
	 * @param array|string $where
	 * @param array $params
	 * @return bool
	 */
	public function update($data = array(), $where = null, $params = array()) {
		return $this->getPdo()->update($this->getName(), $data, $where, $params);
	}

	/**
	 * @param array|string $where
	 * @param array $params
	 * @return bool
	 */
	public function delete($where, $params = array()) {
		return $this->getPdo()->delete($this->getName(), $where, $params);
	}

	/**
	 * @param array|string $where
	 * @param array $params
	 * @return int 
	 */
	public function count($where = null, $params = array()) {
		return $this->getPdo()->count($this->getName(), $where, $params);
	}

	/**
	 * @param string $fields
	 * @return array
	 */
	public function duplicates($fields) {
		return $this->getPdo()->duplicates($this->getName(), $fields);
	}

	/**
	 * @param string $field
	 * @return int
	 */
	public static function max($field = 'id') {
		return $this->getPdo()->max($this->getName(), $field);
	}
	
	/* table helpers */

	/**
	 * @param bool $truncate
	 * @return int
	 */
	public function emptyTable() {
		return $this->getPdo()->empty($this->getName());
	}

	/**
	 * @return int
	 */
	public function dropTable() {
		return $this->getPdo()->dropTable($this->getName());
	}

	/**
	 * @param bool $execute
	 * @param bool $foreignKeys
	 * @return string 
	 */
	public function createTable($execute = true, $foreignKeys = true) {
		$fkFields = array();
		if ($foreignKeys) {
			$fks = static::getForeignKeys();
			foreach ($fks as $fk) {
				$fkFields[$fk['name']] = $fk['table'] . '(' . $fk['column'] . ')';
			}
		}
		$sql = static::getPdo()->createTable(static::getTable(), static::getFields(), $fkFields, static::getPrimaryKeys(), $execute);

		foreach (static::getManyRelations() as $class => $extraFields) {
			$table = static::getManyTable($class);
			$fields = $pkFields = static::getManyKeys($class);
			if (!empty($extraFields)) {
				$fields = array_merge($fields, $extraFields);
			}
			$sql .= static::getPdo()->createTable($table, $fields, array(), $pkFields, $execute);
		}

		return $sql;
	}

	/**
	 * @param bool $execute
	 * @return boolean
	 */
	public function alterTable($execute = true) {
		$pdo = static::getPdo();
		$table = static::getTable();

		$fields = 

		$tableCols = $pdo->listColumns($table);
		$tableFields = array_map(function($i) {
			return $i['name'];
		}, $tableCols);

		$addedFields = array_diff($fields, $tableFields);
		$removedFields = array_diff($tableFields, $fields);

		if (empty($addedFields) || empty($removedFields)) {
			return false;
		}
		return $pdo->alterTable($table, $addedFields, $removeFields, $execute);
	}

	
	public function __toString() {
		return $this->getName();
	}
}