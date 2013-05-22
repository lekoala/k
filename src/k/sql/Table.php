<?php

namespace k\sql;

use \InvalidArgumentException;
use \BadMethodCallException;
use \stdClass;

/**
 * Table class
 * 
 * Table information is done through introspection or by asking DataObjects if
 * they exists
 *
 * select/insert/update/delete are fully hookable by adding definitions in dataobjects
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
	
	public function getNewInstance() {
		$class = $this->getItemClass();
		if($class) {
			return new $class($this->getPdo());
		}
	}

	public function getPrimaryKey() {
		$class = $this->getItemClass();
		if ($class && is_subclass_of($class, '\\k\\sql\\DataObject')) {
			return $class::getPrimaryKey();
		}
		//TODO : introspect db
	}

	public function getPrimaryKeys() {
		$class = $this->getItemClass();
		if ($class && is_subclass_of($class, '\\k\\sql\\DataObject')) {
			return $class::getPrimaryKeys();
		}
		//TODO : introspect db
	}

	public function getForForeignKey($name = null) {
		if (!$name) {
			$name = $this->getName();
		}
		return strtolower($name) . '_' . $this->getPrimaryKey();
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

	public function q() {
		return $this->query();
	}

	public function query() {
		$q = new Query($this->getPdo());
		$q->from($this->getName());
		if ($this->getItemClass()) {
			$q->fetchAs($this->getItemClass());
		}
		return $q;
	}

	/**
	 * 
	 * @param \k\sql\DataObject $do
	 * @return boolean
	 * @throws InvalidArgumentException
	 */
	public function save($do) {
		$class = $this->getItemClass();
		if (!$do instanceof $class) {
			throw new InvalidArgumentException("You must pass a $class to save");
		}
		//save cached objects too
		foreach ($do->getCache() as $name => $o) {
			if (is_object($o)) {
				$o->save();
				$field = $o->getForForeignKey();
				$do->$field = $o->getId();
			}
		}

		$data = $do->toArray();
		if ($do->exists()) {
			$changed = array();
			foreach ($do->getOriginal() as $k => $v) {
				if ($do->$k != $v) {
					$changed[$k] = $do->$k;
				}
			}
			if (empty($changed)) {
				return true;
			}
			$res = $this->update($changed, $do->pkAsArray());
		} else {
			$inserted = array();
			foreach ($data as $k => $v) {
				if (!empty($v)) {
					$inserted[$k] = $v;
				}
			}
			if (empty($inserted)) {
				return false;
			}
			$res = static::insert($inserted);
			if ($res && property_exists($do, 'id')) {
				$do->id = $res;
			}
		}
		return $res;
	}

	/**
	 * @param array|string $where
	 * @param array|string $orderBy
	 * @param array|string $limit
	 * @param array|string $fields
	 * @param array $params
	 * @return array 
	 */
	public function select($where = array(), $orderBy = 'default', $limit = null, $fields = '*', $params = array()) {
		$cl = $this->getItemClass();
		if ($cl) {
			$res = $cl::onBeforeSelect($where, $orderBy, $limit, $fields, $params);
			if ($res === false) {
				return false;
			}
		}
		$where = $this->detectPrimaryKeys($where);
		if ($orderBy == 'default') {
			$orderBy = $this->getDefaultSort();
		}
		$data = $this->getPdo()->select($this->getName(), $where, $orderBy, $limit, $fields, $params);
		if ($cl) {
			$cl::onAfterSelect($data);
		}
		return $data;
	}

	/**
	 * @param array $data
	 * @return int The id of the record
	 */
	public function insert($data = array()) {
		$data = $this->filterData($data);
		$cl = $this->getItemClass();
		if ($cl) {
			$res = $cl::onBeforeInsert($data);
			if ($res === false) {
				return false;
			}
		}
		$res = $this->getPdo()->insert($this->getName(), $data);
		if ($cl) {
			$cl->callHook('onAfterInsert', $res, $data);
		}
		return $res;
	}

	/**
	 * @param array $data
	 * @param array|string $where
	 * @param array $params
	 * @return bool
	 */
	public function update($data = array(), $where = null, $params = array()) {
		$data = $this->filterData($data);
		$where = $this->detectPrimaryKeys($where);

		//detect primary key and use it as where condition
		if (empty($where)) {
			$where = array();
			foreach ($this->getPrimaryKeys() as $pk) {
				if (isset($data[$pk])) {
					$where[$pk] = $data[$pk];
					unset($data[$pk]);
				}
			}
		}
		$cl = $this->getItemClass();
		if ($cl) {
			$res = $cl::onBeforeUpdate($data, $where, $params);
			if ($res === false) {
				return false;
			}
		}

		$res = $this->getPdo()->update($this->getName(), $data, $where, $params);
		if ($cl) {
			$cl::onAfterUpdate($res, $data, $where, $params);
		}
		return $res;
	}

	/**
	 * @param array|string $where
	 * @param array $params
	 * @return bool
	 */
	public function delete($where, $params = array()) {
		$where = $this->detectPrimaryKeys($where);
		$cl = $this->getItemClass();
		if ($cl) {
			$res = $cl::onBeforeDelete($where, $params);
			if ($res === false) {
				return false;
			}
		}
		$res = $this->getPdo()->delete($this->getName(), $where, $params);
		if ($cl) {
			$cl::onAfterDelete($res, $where, $params);
		}
		return $res;
	}

	/**
	 * Handle direct primary keys as params in where
	 * @param int|array $where
	 * @return type
	 * @throws Exception
	 */
	protected function detectPrimaryKeys($where) {
		if (empty($where)) {
			return $where;
		}
		$pkFields = $this->getPrimaryKeys();
		if (is_numeric($where)) {
			if (count($pkFields) > 1) {
				throw new Exception('Only one id for a composed primary keys');
			}
			$where = array($pkFields[0] => $where);
		} elseif (is_array($where)) {
			$values = array();
			foreach ($where as $k => $v) {
				if (is_int($k)) {
					$values[] = $v;
				}
			}
			if (count($values) == count($pkFields)) {
				$where = array_combine($pkFields, $values);
			}
		}
		return $where;
	}

	/**
	 * Do not try to insert or update fields that don't exist
	 * @param array $data
	 * @return array
	 */
	public function filterData($data) {
		$cl = $this->getItemClass();
		if (!$cl) {
			return $data;
		}
		$fields = array_keys($cl::getFields());
		foreach ($data as $k => $v) {
			if (!in_array($k, $fields)) {
				unset($data[$k]);
			}
		}
		return $data;
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

	/**
	 * @param bool $truncate
	 * @return int
	 */
	public static function emptyTable() {
		return static::getPdo()->emptyTable(static::getTable());
	}

	/**
	 * @return int
	 */
	public static function dropTable() {
		return static::getPdo()->dropTable(static::getTable());
	}

	/**
	 * @param bool $execute
	 * @param bool $foreignKeys
	 * @return string 
	 */
	public static function createTable($execute = true, $foreignKeys = true) {
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
	public static function alterTable($execute = true) {
		$pdo = static::getPdo();
		$table = static::getTable();

		$fields = static::getFields();

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

	/**
	 * Get the fluent query builder with class defaults
	 * @return Query
	 */
	public static function getDefault() {
		return static::query()
		->from(static::getTable())
		->fetchAs(get_called_class())
		->where(static::getDefaultWhere())
		->orderBy(static::getDefaultSort())
		;
	}

}