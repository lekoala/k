<?php

namespace k\sql;

use ReflectionClass;
use Exception;
use \JsonSerializable;
use \InvalidArgumentException;

/**
 * DataObject class
 * 
 * A DataObject instance can be used as a standard class, but add extra
 * behaviours to make things easier:
 * - get related records in the database
 * - persistence
 * - virtual getters/setters with underscore syntax (like your db should be!)
 * - validation rules
 * - view friendly usage (do not throw exceptions for undefined properties, null objects)
 * 
 * Add specifications for your DataObjects here in the static properties
 * The DataObject is also used as a record instance, to avoid creating too many
 * classes
 *
 * @author LeKoala
 */
class DataObject implements JsonSerializable {

	/**
	 * Store field data
	 * 
	 * @var array
	 */
	protected $data = array();

	/**
	 * Store original field data
	 * 
	 * @var array
	 */
	protected $original = null;

	/**
	 * Cache resolved objects for the current instance
	 * 
	 * @var array
	 */
	protected $cache = array();

	/**
	 * @var \k\sql\Pdo
	 */
	protected $pdo;

	/**
	 * Enforce field definition like array('id','name','desc' => 'text');
	 * Leave it to null to be freestyle
	 * 
	 * @var null|array 
	 */
	protected static $fields = null;

	/**
	 * Store hasOne relations. Relations are defined as an array
	 * array('Name','OtherName' => 'table')
	 * 
	 * If no table is mapped, the lowercased name of the relation is used
	 * 
	 * @var array 
	 */
	protected static $hasOne = array();

	/**
	 * Store hasMany 
	 * 
	 * If no table is mapped, the singular lowercased name of the relation is used
	 * 
	 * @var array 
	 */
	protected static $hasMany = array();

	/**
	 * Store manyMany
	 * 
	 * If no table is mapped, the singular lowercased name of the relation is used
	 * 
	 * @var array
	 */
	protected static $manyMany = array();

	/**
	 * Store manymany extra fields Name => array('my','extra' => 'datetime', 'field' => 'text')
	 * @var	array
	 */
	protected static $manyManyExtra = array();

	/**
	 * Folder to store items related to this class
	 * @var string
	 */
	protected static $storage;

	/**
	 * Store validation rules
	 * @var array 
	 */
	protected static $validation = array();

	public function __construct($pdo) {
		$this->setPdo($pdo);
		$this->original = $this->data;
	}

	public function __get($name) {
		return $this->getField($name);
	}

	public function __set($name, $value) {
		//the class is not yet initialized, just inject data into it
		if ($this->original === null) {
			$this->data[$name] = $value;
			return;
		}
		return $this->setField($name, $value);
	}

	/**
	 * Get a property or a virtual property
	 * @param string $name
	 * @return string
	 */
	public function getField($name) {
		$method = 'get_' . $name;
		//virtual field
		if (method_exists($this, $method)) {
			return $this->$method();
		}
		//field
		elseif (array_key_exists($name, $this->data)) {
			return $this->data[$name];
		}
		//relation
		elseif (!$name) {
			$o = $this->getRelated($name);
			if ($o) {
				return $o;
			}
		}
		return null;
	}

	/**
	 * Set a property or a virtual property
	 * @param string $name
	 * @param mixed $value
	 * @return \K\Orm
	 */
	public function setField($name, $value) {
		if (is_object($value)) {
			if (is_subclass_of($value, '\\k\\sql\\DataObject')) {
				$object = $value;
				if ($object->exists()) {
					$value = $value->getId();
				}
			} else {
				switch (get_class($value)) {
					case 'K\File':
						$folder = static::getBaseFolder();
						while (true) {
							$filename = uniqid($name) . '.' . $value->getExtension();
							if (!file_exists($folder . '/' . $filename))
								break;
						}
						$value->rename($folder . '/' . $filename);
						$value = $filename;
						break;
					default:
						$value = (string) $value;
						break;
				}
			}
		} elseif (is_array($value)) {
			$value = implode(',', $value);
		}
		$method = 'set_' . $name;
		//virtual property setter
		if (method_exists($this, $method)) {
			$this->$method($value);
			return true;
		} elseif (array_key_exists($name, $this->data)) {
			$this->data[$name] = $value;
			return true;
		}
		//relation
		else {
			if (in_array($name, static::getManyExtraFields())) {
				$this->$name = $value;
			} else {
				$name = str_replace('_id', '', $name); //we might try to set this because of a join
				if ($this->isRelated($name) && isset($object)) {
					return $this->addRelated($object);
				}
			}
		}
		return false;
	}

	/**
	 * Check if the raw field exists or is available as a virtual field
	 * @param type $name
	 * @return boolean
	 */
	public function hasField($name) {
		if ($this->hasRawField($name)) {
			return true;
		}
		if (method_exists($this, 'get_' . $name)) {
			return true;
		}
		return false;
	}

	/**
	 * Check if the field exists in the data or in the fields definition
	 * @param string $name
	 * @return boolean
	 */
	public function hasRawField($name) {
		if (array_key_exists($name, $this->data)) {
			return true;
		}
		if (self::$fields && array_key_exists($name, self::$fields)) {
			return true;
		}
		return false;
	}

	/**
	 * Get raw value from a field
	 * 
	 * @param string $name
	 * @return string
	 */
	public function getRawField($name) {
		if ($this->hasRawField($name)) {
			return $this->data[$name];
		}
	}

	/**
	 * Get raw value from a field
	 * 
	 * @param string $name
	 * @return string
	 */
	public function setRawField($name, $value) {
		if ($this->hasRawField($name)) {
			$this->data[$name] = $value;
			return true;
		}
		return false;
	}

	/**
	 * Populate object
	 * 
	 * @param array $data
	 * @param bool $withValue Only set property with a value
	 * @param bool $noOverwrite Do not overwrite property with a value
	 */
	public function populate(array $data = null, $withValue = false, $noOverwrite = false) {
		if ($data === null) {
			$data = array_merge($_GET, $_POST);
			if (isset($data[get_called_class()])) {
				$data = $data[get_called_class()];
			}
		}
		foreach ($data as $k => $v) {
			if (property_exists($this, $k)) {
				if ($withValue && empty($v)) {
					continue;
				}
				if ($noOverwrite && !empty($this->$k)) {
					continue;
				}
				$this->$k = $v;
			}
		}
	}

	/**
	 * Export object to an array
	 * @param string|array $virtual Add virtual properties to the array
	 * @param string|array $only Restrict to these fields only
	 * @return array
	 */
	public function toArray($virtual = array(), $only = array()) {
		$data = $this->data;
		$arr = array();
		if (is_string($only)) {
			$only = explode(',', $only);
		}
		if (!empty($only)) {
			foreach ($data as $k => $v) {
				if (!in_array($k, $only)) {
					continue;
				}
				$arr[$k] = $v;
			}
		} else {
			$arr = $data;
		}
		if (is_string($virtual)) {
			$virtual = explode(',', $virtual);
		}
		foreach ($virtual as $v) {
			$data[$v] = $this->$v;
		}
		return $data;
	}

	/**
	 * Enum list values based on class constants. Constants MUST_LOOK_LIKE_THIS
	 * 
	 * @staticvar array $constants
	 * @param string $name
	 * @return array
	 */
	public static function enum($name) {
		static $constants;

		$name = strtoupper($name);

		if ($constants === null) {
			$ref = new ReflectionClass(get_called_class());
			$constants = $ref->getConstants();
		}

		$enum = array();
		foreach ($constants as $key => $value) {
			if (strpos($key, $name) === 0) {
				$key = strtolower(str_replace($name . '_', '', $key));
				$enum[$key] = $value;
			}
		}
		return $enum;
	}

	/**
	 * Convert the object in array for json
	 * 
	 * @return array
	 */
	public function jsonSerialize() {
		return $this->data;
	}

	/**
	 * Create a fake record in the db
	 * @param bool $save
	 * @return \K\Orm
	 */
	public static function createFake($save = true) {
		$o = new static();
		$fields = static::getFieldsType();
		$pkFields = static::getPrimaryKeys();
		$fkFields = static::getForeignKeys();

		$words = array('Lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur',
			'adipisicing', 'elit', 'sed', 'do', 'eiusmod', 'tempor',
			'incididunt', 'ut', 'labore', 'et', 'dolore', 'magna', 'aliqua');

		foreach ($fields as $field => $type) {
			if (in_array($field, $pkFields)) {
				continue;
			}
			if (in_array($field, $fkFields)) {
				continue;
			}
			$value = $words[array_rand($words)] . ' ' . $words[array_rand($words)] . ' ' . $words[array_rand($words)];
			if (strpos($type, 'INT') !== false) {
				if ($type == 'TINYINT') {
					$value = rand(0, 1);
				} else {
					$value = rand(0, 100);
				}
			} elseif (strpos($type, 'VARCHAR') !== false) {
				$len = preg_replace("/[^0-9,.]/", "", $type);
				$value = substr($value, 0, $len);
			} elseif (strpos($type, 'DECIMAL') !== false || strpos($type, 'FLOAT') !== false) {
				$value = rand(0, 100) . '.' . rand(0, 100);
			} elseif ($type == 'TEXT') {
				$v = '';
				for ($i = 0; $i < 100; $i++) {
					$v .= $words[array_rand($words)];
					if ($i % 20) {
						$v .= ", ";
					}
					if ($i % 20) {
						$v .= ".\n";
					}
				}
				$value = $v;
			} elseif ($type == 'DATE') {
				$value = date('Y-m-d', (time() - rand(0, 31556926)));
			} elseif ($type == 'DATETIME') {
				$value = date('Y-m-d H:i:s', (time() - rand(0, 31556926)));
			} elseif ($type == 'TIME') {
				$value = date('H:i:s', (time() - rand(0, 31556926)));
			}
			$o->$field = $value;
		}
		if ($save) {
			$o->save();
		}
		return $o;
	}

	/**
	 * Get a property as a class that has a create method
	 * @param string $name
	 * @param string $class
	 * @return Object
	 */
	public function getAs($name, $class) {
		if (isset($this->cache[$name])) {
			return $this->cache[$name];
		}
		$this->cache[$name] = $class::create($this->getField($name));
		return $this->cache[$name];
	}

	/**
	 * Get a property as a date
	 * @param string $name
	 * @return \k\Date
	 */
	public function getAsDate($name) {
		return static::getAs($name, '\k\type\Date');
	}

	/**
	 * Get a property as file. File is stored in base_folder/property_value
	 * @param string $name
	 * @return K\File
	 */
	public function getAsFile($name) {
		$path = static::getBaseFolder() . '/' . $this->getField($name);
		return static::getAs($path, 'File');
	}

	/**
	 * Get base storage folder
	 * @param bool $create
	 * @return string
	 */
	public static function getBaseFolder($create = false) {
		$folder = static::$storage . '/' . static::getTableName();
		if ($create && !is_dir($folder)) {
			mkdir($folder);
		}
		return $folder;
	}

	/**
	 * Get instance storage folder
	 * @param bool $create
	 * @return string
	 */
	public function getFolder($create = false) {
		$folder = static::getBaseFolder() . '/' . $this->getId();
		if ($create && !is_dir($folder)) {
			mkdir($folder);
		}
		return $folder;
	}

	/**
	 * Get sub folder
	 * 
	 * @param string $folder
	 * @param bool $create
	 * @return string
	 */
	public function getSubFolder($folder, $create = false) {
		$folder = $this->getFolder() . '/' . $folder;
		if ($create && !is_dir($folder)) {
			mkdir($folder, 0777, true);
		}
		return $folder;
	}

	public function getCache($name = null) {
		if(!$name) {
			return $this->cache;
		}
		if(isset($this->cache[$name])) {
			return $this->cache[$name];
		}
	}
	
	public function setCache($name = null,$value = null) {
		if($name === null) {
			$this->cache = array();
		}
		$this->cache[$name] = $value;
	}

	/**
	 * Shortcut for getRelated
	 * @param string $name
	 * @param array $arguments
	 * @return array
	 */
	public function __call($name, $arguments) {
		return $this->getRelated($name);
	}

	/**
	 * Inject records for this table in an array of records
	 * @param array $array
	 * @param string $relation
	 */
	public static function inject(array &$array, $relation = '') {
		if (empty($array)) {
			return;
		}
		$first = $array[0];
		$class = get_called_class();
		$injectClass = get_class($first);
		$type = $first->isRelated($class, $relation);
		if (!$type) {
			return;
		}
		$table = static::getTable();
		$pk = static::getPrimaryKey();
		$fk = $injectClass::getForForeignKey($relation);
		$classFk = $recordColumn = $column = static::getForForeignKey($relation);

		if ($type == 'hasMany' || $type == 'manyMany') {
			$recordColumn = $pk;
			$column = $injectClass::getForForeignKey($relation);
		}

		$ids = array();
		foreach ($array as $record) {
			$key = $record->$recordColumn;
			if (!$key) {
				continue;
			}
			$ids[] = $key;
		}
		if (empty($ids)) {
			return;
		}
		$ids = array_unique($ids);

		switch ($type) {
			case 'hasOne':
				$injected = static::get()->where($pk, $ids)->orderBy($pk . ' ASC')->fetchAll();
				$byId = array();
				foreach ($injected as $record) {
					$byId[$record->$pk] = $record;
				}
				foreach ($array as $record) {
					$key = $record->$column;
					if (isset($byId[$key])) {
						$o = $byId[$key];
					} else {
						//if we inject something, make sure we have at least a null class
						$o = new $class;
						$o->$pk = $key;
					}
					$record->cache[$column] = $o;
				}
				break;
			case 'hasMany':
				$injected = static::get()->where($fk, $ids)->orderBy($pk . ' ASC')->fetchAll();
				foreach ($array as $record) {
					$id = $record->getId();
					$arr = array();
					foreach ($injected as $i) {
						if ($i->$column == $record->getId()) {
							$arr[] = $i;
						}
					}
					$record->cache[$table] = $arr;
				}
				break;
			case 'manyMany':
				$manyTable = static::getManyTable($injectClass);
				if (empty($manyTable)) {
					$manyTable = $injectClass::getManyTable($class);
				}
				$injectTable = $injectClass::getTable();
				$injectPk = $injectClass::getPrimaryKey();
				$injected = static::get()
				->fields($table . '.*')
				->innerJoin($manyTable, $manyTable . '.' . $fk . ' = ' . $injectTable . '.' . $injectPk)
				->innerJoin($injectTable, $injectTable . '.' . $injectPk . ' = ' . $manyTable . '.' . $fk)
				->where($manyTable . '.' . $fk, $ids)->orderBy(null)->fetchAll();
				$byId = array();
				foreach ($injected as $record) {
					$byId[$record->$pk] = $record;
				}
				$map = $injectClass::get()
				->fields($manyTable . '.' . $classFk . ',' . $manyTable . '.' . $fk)
				->innerJoin($manyTable, $manyTable . '.' . $fk . ' = ' . $injectTable . '.' . $injectPk)
				->where($manyTable . '.' . $fk, $ids)->orderBy(null)->fetchAll(PDO::FETCH_ASSOC);

				foreach ($array as $record) {
					$id = $record->getId();
					$arr = array();
					foreach ($map as $row) {
						$recordId = $row[$fk];
						$injectedId = $row[$classFk];
						if ($recordId == $id && isset($byId[$injectedId])) {
							$arr[] = $byId[$injectedId];
						}
					}
					$record->cache[$table] = $arr;
				}
				break;
		}
	}
	
	/**
	 * Get related objects
	 * @param string $name
	 * @return array
	 */
	public function getRelated($name) {
		$cache = $this->getCache($name);
		if($cache) {
			return $cache;
		}
		$type = self::isRelated($name);
		if (!$type) {
			return false;
		}
		$relations = self::getAllRelations();
		$table = $relations[$name];
		$ft = $this->getPdo()->t($table);
		
		$data = null;
		switch ($type) {
			case 'hasOne' :
				$pkField = $ft->getPrimaryKey();
				$field = $ft->getForForeignKey($name);
				$q = $ft->q()->where($pkField, $this->$field);
				$data = $q->fetchOne();
				if(!$data) {
					$data = $ft->getNewInstance();
				}
				break;
			case 'hasMany' :
				$q = $ft->q()->where($this->getForForeignKey(), $this->getId());
				$data = $q->fetchAll();
				break;
			case 'manyMany' :
				$table = $name::getTable();
				if (isset($this->cache[$table])) {
					return $this->cache[$table];
				}
				$q = $name::get()
				->fields($table . '.*')
				->innerJoin(static::getManyTable($name), $name::getTable() . '.' . $name::getPrimaryKey() . ' = ' . static::getManyTable($name) . '.' . $name::getForForeignKey())
				->where(static::getForForeignKey(), $this->id);
				$data = $q->fetchAll();
				break;
		}
		$this->cache[$name] = $data;
		return $data;
	}

	/**
	 * Add related object(s)
	 * @param K\Orm|array $o
	 * @param string $relation
	 * @return boolean
	 */
	public function addRelated($o, $relation = '') {
		if (is_array($o)) {
			foreach ($o as $obj) {
				$this->addRelated($obj);
			}
			return true;
		}

		if (!$o) {
			return;
		}
		$class = get_class($o);
		$type = $this->isRelated($class, $relation);
		switch ($type) {
			case 'hasOne':
				$field = $class::getForForeignKey($relation);
				$this->$field = $o->getId();
				$this->cache[$field] = $o;
				return $this->save();
				break;
			case 'hasMany':
				$table = $class::getTable();
				$field = static::getForForeignKey();
				$o->$field = $this->getId();
				$this->clearCache($table);
				return $o->save();
				break;
			case 'manyMany':
				$table = $class::getTable();
				$where = array(static::getForForeignKey() => $this->id, $class::getForForeignKey() => $o->getId());
				$count = static::getPdo()->count(static::getManyTable($class), $where);
				if (!$count) {
					$this->clearCache($table);
					return static::getPdo()->insert(static::getManyTable($class), $where);
				}
				return false;
				break;
		}
	}

	/**
	 * Remove related object(s)
	 * @param K\Orm|array $o
	 * @param string $relation
	 * @return boolean
	 */
	public function removeRelated($o = null, $relation = '') {
		if (is_array($o)) {
			foreach ($o as $obj) {
				$this->removeRelated($obj);
			}
			return true;
		}

		$class = get_class($o);
		$type = $this->isRelated($class, $relation);

		switch ($type) {
			case 'hasOne':
				$field = $class::getForForeignKey($relation);
				$this->$field = null;
				return $this->save();
				break;
			case 'hasMany':
				$field = static::getForForeignKey();
				$o->$field = null;
				return $o->save();
				break;
			case 'manyMany':
				$where = array(static::getForForeignKey() => $this->id, $class::getForForeignKey() => $o->getId());
				return static::getPdo()->delete(static::getManyTable($class), $where);
				break;
		}
	}

	/**
	 * Empty cache
	 * @return \K\Orm
	 */
	public function emptyCache() {
		$this->cache = array();
		return $this;
	}

	/**
	 * Does the record exist
	 * @param bool $db Check if it really exists in the db
	 * @return boolean
	 */
	public function exists($db = false) {
		$pkFields = static::getPrimaryKeys();
		if ($db) {
			return static::count($this->pkAsArray());
		}
		foreach ($pkFields as $field) {
			if ($this->$field != '') {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get primary key as array
	 * @return array
	 */
	protected function pkAsArray() {
		$arr = array();
		foreach (static::getPrimaryKeys() as $field) {
			$arr[$field] = $this->$field;
		}
		return $arr;
	}

	/**
	 * Save the record
	 * @return boolean
	 */
	public function save() {
		return $this->getTable()->save($this);
	}

	public function onPreRemove() {
		//implement in subclass
	}

	public function onPostRemove() {
		//implement in subclass
	}

	/**
	 * Remove record from db
	 * @return boolean
	 */
	public function remove() {
		if ($this->exists()) {
			$res = $this->onPreRemove();
			if ($res === false) {
				return false;
			}
			$res = static::delete($this->pkAsArray());
			$this->onPostRemove();
			return $res;
		}
		return false;
	}

	/**
	 * Remove record and related records from db
	 * @return boolean
	 */
	public function erase() {
		return $this->remove();
	}

	public function __toString() {
		return get_called_class() . json_encode($this->pkAsArray());
	}

	/**
	 * Simple td representation
	 * @param string|array $fields
	 * @return string
	 */
	public function row($fields = null) {
		if ($fields === null) {
			$fields = static::getFields();
		}
		if (is_string($fields)) {
			$fields = explode(',', $fields);
			$fields = array_map('trim', $fields);
		}
		$html = '';
		foreach ($fields as $field) {
			$value = null;
			if (strpos($field, '.') !== false) {
				$parts = explode('.', $field);
				$key = $parts[0];
				$field = $parts[1];
				$obj = $this->getRelated($key);
				if ($obj) {
					$value = $obj->getField($field);
				}
			} else {
				$value = $this->getField($field);
			}
			$html .= "<td>" . $value . "</td>\n";
		}
		return $html;
	}

	/**
	 * @return Pdo
	 */
	public function getPdo() {
		return $this->pdo;
	}

	/**
	 * @param PDO $pdo
	 * @return \k\db\Orm
	 */
	public function setPdo($pdo) {
		if (!$pdo instanceof \k\sql\Pdo) {
			throw new InvalidArgumentException('Must be an instance of pdo');
		}
		$this->pdo = $pdo;
	}

	public static function getStorage() {
		return static::$storage;
	}

	public static function setStorage($storage) {
		static::$storage = $storage;
	}

	/**
	 * Get table instance
	 * @return \k\sql\Table
	 */
	public function getTable() {
		return $this->getPdo()->t(self::getTableName());
	}

	public static function getTableName() {
		$name = str_replace(Table::$classPrefix, '',get_called_class());
		return strtolower($name);
		return strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $name));
	}

	/**
	 * Get many table
	 * @param string $class
	 * @return string
	 */
	public static function getManyTable($class) {
		if (in_array($class, static::getManyRelations(true))) {
			return static::getTable() . '_' . $class::getTable();
		}
		return false;
	}

	/**
	 * Build relation array
	 * 
	 * Set table name as lowercased singular if not specified
	 * 
	 * @param array $arr Fields definition
	 * @return array
	 */
	protected static function buildRelationsArray($arr) {
		$relations = array();
		foreach ($arr as $name => $table) {
			if (is_int($name)) {
				$name = $table;
				$table = preg_replace('/s$/', '', strtolower($name));
			}
			$relations[$name] = $table;
		}
		return $relations;
	}

	/**
	 * Check if a named relations exists
	 * 
	 * @param string $name
	 * @return string|boolean
	 */
	public static function isRelated($name) {
		if (in_array($name, self::getHasOneRelations(true))) {
			return 'hasOne';
		}
		if (in_array($name, self::getHasManyRelations(true))) {
			return 'hasMany';
		}
		if (in_array($name, self::getManyManyRelations(true))) {
			return 'manyMany';
		}
		return false;
	}

	/**
	 * Get all relations
	 * 
	 * @staticvar null $relations
	 * @param bool $keys
	 * @return array
	 */
	public static function getAllRelations($keys = false) {
		static $relations = null;

		if ($relations === null) {
			$relations = array_merge(self::getHasOneRelations(), self::getHasManyRelations(), self::getManyManyRelations());
		}

		if ($keys) {
			return array_keys($relations);
		}

		return $relations;
	}
	
	/**
	 * Get many relations
	 * @param bool $keys
	 * @return array
	 */
	public static function getHasOneRelations($keys = false) {
		static $relations = null;

		if ($relations === null) {
			$relations = self::buildRelationsArray(static::$hasOne);
		}

		if ($keys) {
			return array_keys($relations);
		}

		return $relations;
	}

	/**
	 * Get many relations
	 * @param bool $keys
	 * @return array
	 */
	public static function getHasManyRelations($keys = false) {
		static $relations = null;

		if ($relations === null) {
			$relations = self::buildRelationsArray(static::$hasMany);
		}

		if ($keys) {
			return array_keys($relations);
		}

		return $relations;
	}

	/**
	 * Get many relations
	 * @param bool $keys
	 * @return array
	 */
	public static function getManyManyRelations($keys = false) {
		static $relations = null;

		if ($relations === null) {
			$relations = self::buildRelationsArray(static::$manyMany);
		}

		if ($keys) {
			return array_keys($relations);
		}

		return $relations;
	}

	/**
	 * Get all extra fields that could be defined through a manyMany relations
	 * @staticvar null $extraFields
	 * @return array
	 */
	public static function getManyExtraFields() {
		static $extraFields = null;
		if ($extraFields === null) {
			$extraFields = array_values(static::getManyRelations());
		}
		return $extraFields;
	}

	/**
	 * Get many primary keys
	 * @param string $class
	 * @return array
	 */
	public static function getManyKeys($class) {
		return array(
			static::getTable() . '_' . static::getPrimaryKey(),
			$class::getTable() . '_' . $class::getPrimaryKey()
		);
	}

	/**
	 * Get fields
	 * @staticvar array $fields
	 * @return array
	 */
	public static function getFields($keys = true) {
		static $fields;

		if ($fields === null) {
			$fields = array();
			$fieldsDefinition = static::$fields;
			foreach ($fieldsDefinition as $name => $class) {
				if (is_int($name)) {
					$name = $class;
					$class = '';
				}
				$fields[$name] = $class;
			}
		}
		
		if($keys) {
			return array_keys($fields);
		}

		return $fields;
	}

	/**
	 * Get fields => type
	 * @staticvar array $fieldstype
	 * @return array
	 */
	public static function getFieldsType() {
		static $fieldstype;

		if ($fieldstype === null) {
			$fieldstype = array();
			$fields = static::getFields();
			$pdo = static::getPdo();
			foreach ($fields as $field) {
				$fieldstype[$field] = $pdo->nameToType($field);
			}
		}

		return $fieldstype;
	}

	public function getId($mustExist = false) {
		$pkField = static::getPrimaryKey();
		$value = $this->$pkField;
		if (empty($value) && $mustExist) {
			throw new Exception('This record does not have an id yet');
		}
		return $value;
	}
	
	public function getOriginal() {
		return $this->original;
	}

	public static function getPrimaryKey() {
		$pkFields = static::getPrimaryKeys();
		if (count($pkFields) !== 1) {
			throw new Exception('This method only support table with one primary key');
		}

		return $pkFields[0];
	}

	/**
	 * Get primary keys
	 * @staticvar array $pkFields
	 * @return array
	 */
	public static function getPrimaryKeys() {
		static $pkFields;

		if ($pkFields === null) {
			$pkFields = array();
			$fields = array_keys(static::getFields());
			if (in_array('id', $fields)) {
				$pkFields[] = 'id';
			} else {
				$fkFields = static::getForeignKeys();
				foreach ($fields as $field) {
					//link table
					if (in_array($field, $fkFields)) {
						$pkFields[] = $field;
					} else {
						//at least one pk
						if (empty($fields)) {
							$pkFields[] = $field;
						}
						break; //break
					}
				}
			}
		}

		return $pkFields;
	}

	/**
	 * Create a field name to be used as a foreign key field for this table
	 * @param string $relation
	 * @return string
	 * @throws Exception
	 */
	public function getForForeignKey($name = null) {
		return $this->getTable()->getForForeignKey($name = null);
	}

	/**
	 * Get foreign keys based on naming convetions. No query is done on the db.
	 * The foreign key fields must follow the class_field or class_[rel]_field convention
	 * @staticvar array $fkFields
	 * @return array
	 */
	public static function getForeignKeys() {
		static $fkFields;

		if ($fkFields === null) {
			$fkFields = array();
			$fields = static::getFields();
			foreach ($fields as $field) {
				$parts = explode('_', $field);
				if (count($parts) < 2) {
					continue;
				}
				$table = $parts[0];
				$relation = $parts[(count($parts) - 2)];
				if ($table == $relation) {
					$relation = '';
				}
				$pk = end($parts);

				$class = ucfirst(str_replace(' ', '', ucwords(preg_replace('/[^A-Z^a-z^0-9]+/', ' ', $table))));
				if (class_exists($class, false) && is_subclass_of($class, __CLASS__) && property_exists($class, $pk)) {
					$table = $class::getTable();
					$fk = array(
						'name' => $field,
						'table' => $table,
						'column' => $pk,
						'relation' => $relation
					);
					$fkFields[] = $fk;
				}
			}
		}

		return $fkFields;
	}

	/**
	 * Default sort
	 * @staticvar string $sort
	 * @return string
	 */
	public static function getDefaultSort() {
		static $sort;

		if (empty($sort)) {
			$pk = static::getPrimaryKeys();
			array_walk($pk, function(&$item) {
				$item = $item . ' ASC';
			});
			$sort = implode(',', $pk);
		}
		return $sort;
	}

	/**
	 * Default filtering options
	 * @return string
	 */
	public static function getDefaultWhere() {
		return array();
	}
	
	public static function onBeforeSelect(&$where,&$orderBy,&$limit,&$fields,&$params) {
		
	}
	public static function onAfterSelect(&$data) {
		
	}
	public static function onBeforeInsert(&$data,&$params = null) {
		
	}
	public static function onAfterInsert(&$res,$data,$params = null) {
		
	}
	public static function onBeforeUpdate(&$data,&$where = null,&$params = null) {
		
	}
	public static function onAfterUpdate(&$res,$data,$where = null,$params = null) {
		
	}
	public static function onBeforeDelete(&$data,&$where = null,&$params = null) {
		
	}
	public static function onAfterDelete(&$res, $data,$where = null,$params = null) {
		
	}
	
	/**
	 * Alias getPdo()->getTable()
	 * @param string $table
	 */
	public function t($table) {
		return $this->getPdo()->getTable($table);
	}
}
