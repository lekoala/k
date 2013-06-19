<?php

namespace k\db;

use \ReflectionClass;
use \Exception;
use \JsonSerializable;
use \InvalidArgumentException;
use \ArrayAccess;
use \Iterator;

/**
 * Orm class
 * 
 * A Orm instance can be used as a standard class, but add extra
 * behaviours to make things easier:
 * - get related records in the database
 * - persistence
 * - virtual getters/setters with underscore syntax (like your db should be!)
 * - validation rules
 * - view friendly usage (do not throw exceptions for undefined properties, null objects)
 * 
 * Add specifications for your Orm here in the static properties
 * The Orm is also used as a record instance, to avoid creating too many
 * classes
 *
 * @author LeKoala
 */
class Orm implements JsonSerializable, ArrayAccess, Iterator {

	const HAS_ONE = 'hasOne';
	const HAS_MANY = 'hasMany';
	const MANY_MANY = 'manyMany';

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
	 * Connection name
	 * @var string
	 */
	protected static $connection = 'default';

	/**
	 * Enforce field definition like ['id','name','desc' => 'text'];
	 * Leave it to null to be freestyle
	 * 
	 * @var null|array 
	 */
	protected static $fields = null;

	/**
	 * Store has-one relations.
	 * 
	 * Relations are defined as an array like ['Name','OtherName' => 'Class']
	 * 
	 * @var array 
	 */
	protected static $hasOne = array();

	/**
	 * Store has-many
	 * 
	 * @var array 
	 */
	protected static $hasMany = array();

	/**
	 * Store many-many relations
	 * 
	 * @var array
	 */
	protected static $manyMany = array();

	/**
	 * Store many-many extra fields like ['relation' => ['my','extra' => 'datetime', 'field' => 'text']]
	 * @var	array
	 */
	protected static $manyManyExtra = array();

	/**
	 * Base folder to use for file storage
	 * @var string
	 */
	protected static $storage;

	/**
	 * Store validation rules
	 * @var array 
	 */
	protected static $validation = array();

	/**
	 * Default additionnal fields to export with toArray()
	 * @var array
	 */
	protected static $exportableFields = array();

	public function __construct($data = null) {
		if (is_array($data)) {
			$this->data = $data;
		}
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
	 * Get a property or a virtual property.
	 * 
	 * $o->getField('firstname') -> data['firstname']
	 * $o->getField('virtual') => get_virtual
	 * $o->getField('Employee') => getRelated('Employee')
	 * $o->getField('Employee.name') => getRelated('Employee')->name
	 * 
	 * @param string $name
	 * @return string
	 */
	public function getField($name) {
		if(strpos($name, '.') !== false) {
			$parts = explode('.', $name);
			$part = array_shift($parts);
			if($this->isRelated($part)) {
				return $this->getRelated($part)->getField(implode('.',$parts));
			}
		}
		//virtual field
		$method = 'get_' . $name;
		if (method_exists($this, $method)) {
			return $this->$method();
		}
		//field
		elseif (array_key_exists($name, $this->data)) {
			return $this->data[$name];
		}
		//relation
		elseif (!$name) {
			$rel = $this->getRelated($name);
			if ($rel) {
				return $rel;
			}
		}
		return null;
	}

	/**
	 * Set a property or a virtual property
	 * 
	 * @param string $name
	 * @param mixed $value
	 * @return \k\db\Orm
	 */
	public function setField($name, $value) {
		if (is_object($value)) {
			if (is_subclass_of($value, '\\k\\sql\\Orm')) {
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
	 * Check if the field exists or is available as a virtual field
	 * @param type $name
	 * @return boolean
	 */
	public function hasField($name) {
		if(strpos($name, '.') !== false) {
			$parts = explode('.',$name);
			if($this->isRelated($parts[0])) {
				return true;
			}
		}
		if ($this->hasRawField($name)) {
			return true;
		}
		if (method_exists($this, 'get_' . $name)) {
			return true;
		}
		if($this->isRelated($name)){
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
		if (static::$fields && array_key_exists($name, static::getFields())) {
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
		if (array_key_exists($name, $this->data)) {
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
	 * 
	 * @param string|array $fields Fields to export
	 * @return array
	 */
	public function toArray($fields = null) {
		$data = $this->data;
		$arr = array();
		if (is_string($fields)) {
			$fields = explode(',', $fields);
		}
		foreach ($data as $k => $v) {
			if (!empty($fields) && !in_array($k, $fields)) {
				continue;
			}
			$arr[$k] = $this->getField($k);
		}
		return $arr;
	}

	/**
	 * Get all traits applied to this object
	 * 
	 * @staticvar array $traits
	 * @return array
	 */
	public static function getTraits() {
		static $traits;

		if ($traits === null) {
			$ref = new ReflectionClass(get_called_class());
			$ts = $ref->getTraitNames();
			$traits = array();
			foreach ($ts as $trait) {
				$name = explode('\\', $trait);
				$name = end($name);
				$traits[$trait] = $name;
			}
		}

		return $traits;
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
		return $this->toArray(static::$exportableFields);
	}

	/**
	 * Create a fake record in the db
	 * 
	 * @param bool $save
	 * @return \k\db\Orm
	 */
	public static function createFake($save = true) {
		$o = new static();
		$fields = static::getFields();
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
	 * 
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
		if (!$name) {
			return $this->cache;
		}
		if (isset($this->cache[$name])) {
			return $this->cache[$name];
		}
	}

	public function setCache($name = null, $value = null) {
		if ($name === null) {
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
				$manyTable = static::getManyManyTable($injectClass);
				if (empty($manyTable)) {
					$manyTable = $injectClass::getManyManyTable($class);
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
	 * @param string $type
	 * @param string $class
	 * @return array
	 */
	public function getRelated($name, $type = null, $class = null) {
		$cache = $this->getCache($name);
		if ($cache) {
			return $cache;
		}
		if ($type === null) {
			$type = static::isRelated($name);
		}
		if (!$type) {
			return false;
		}
		if ($class === null) {
			$relations = static::getAllRelations();
			$class = $relations[$name];
		}
		$data = null;
		switch ($type) {
			case static::HAS_ONE:
				$pkField = $class::getPrimaryKey();
				$field = $class::getForForeignKey($name);
				$value = $this->$field;
				$data = null;
				if($value) {
					$q = $class::query()->where($pkField, $value);
					$data = $q->fetchOne();
				}
				if (!$data) {
					$data = new $class;
				}
				break;
			case static::HAS_MANY:
				$q = $class::query()->where($this->getForForeignKey(), $this->getId());
				$data = $q->fetchAll();
				break;
			case static::MANY_MANY:
				$manyTable = $this->getManyManyTable($name);
				$q = $class::query()
				->innerJoin($manyTable, $class::getTableName() . '.' . $class::getPrimaryKey() . ' = ' . $class::getForForeignKey())
				->where($this->getForForeignKey(), $this->id);
				//fetch extra fields
				$extra = static::getManyExtraFields($name);
				foreach ($extra as $name => $type) {
					$q->addField("$manyTable.$name AS extra_$name");
				}
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
				$count = static::getPdo()->count(static::getManyManyTable($class), $where);
				if (!$count) {
					$this->clearCache($table);
					return static::getPdo()->insert(static::getManyManyTable($class), $where);
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
				return static::getPdo()->delete(static::getManyManyTable($class), $where);
				break;
		}
	}

	/**
	 * Empty cache
	 * @return \k\db\Orm
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
		//save cached objects too
		foreach ($this->getCache() as $name => $o) {
			if (is_object($o)) {
				if (is_subclass_of($o, '\\k\\sql\\Orm')) {
					$o->save();
					$class = get_class($o);
					$field = $class::getForForeignKey();
					$this->$field = $o->getId();
				}
			}
		}

		$data = $this->toArray();
		if ($this->exists()) {
			$changed = array();
			foreach ($this->getOriginal() as $k => $v) {
				if ($this->$k != $v) {
					$changed[$k] = $this->$k;
				}
			}
			if (empty($changed)) {
				return true;
			}
			$res = static::getTable()->update($changed, $this->pkAsArray());
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
			$res = static::getTable()->insert($inserted);
			if ($res && property_exists($this, 'id')) {
				$this->id = $res;
			}
		}
		return $res;
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
	 * Get pdo instance for this Orm
	 * 
	 * @return Pdo
	 */
	public static function getPdo() {
		return Pdo::get(static::$connection);
	}

	public static function getConnection() {
		return static::$connection;
	}

	public static function setConnection($connection) {
		static::$connection = $connection;
	}

	public static function getStorage() {
		return static::$storage;
	}

	public static function setStorage($storage) {
		static::$storage = $storage;
	}

	public function t($name = null) {
		return static::getTable($name);
	}

	/**
	 * Get table instance
	 * 
	 * @return \k\db\Table
	 */
	public static function getTable($name = null) {
		if ($name === null) {
			$name = static::getTableName();
		}
		return static::getPdo()->t($name);
	}

	/**
	 * Convert {PREFIX}MyClass to my_class
	 * 
	 * @return string
	 */
	public static function getTableName() {
		$name = str_replace(Table::$classPrefix, '', get_called_class());
		return strtolower($name);
	}

	/**
	 * Get many table
	 * 
	 * 
	 * @param string $name
	 * @return string
	 */
	public function getManyManyTable($name) {
		foreach ($this->getManyManyRelations() as $relation => $class) {
			if ($name == $relation) {
				return static::getTableName() . '_' . $class::getTableName();
			}
		}
		return false;
	}

	/**
	 * Provide basic english singularization for most common cases
	 * 
	 * @param string $word
	 * @return string
	 */
	protected static function singularize($word) {
		$rules = array(
			'ies' => 'y',
			'ss' => 'sss',
			's' => ''
		);

		foreach ($rules as $r => $rpl) {
			$word = preg_replace('/' . $r . '$/', $rpl, $word);
		}

		return $word;
	}

	/**
	 * Build relation array
	 * 
	 * Set class as singular
	 * 
	 * @param array $arr Fields definition
	 * @return array
	 */
	protected static function buildRelationsArray($arr, $type = null) {
		$relations = array();

		if ($type) {
			//add extensions fields
			foreach (static::getTraits() as $t => $name) {
				$m = $type . $name;
				if (method_exists($t, $m)) {
					$relations = array_merge($relations, $t::$$m());
				}
			}
		}

		foreach ($arr as $name => $class) {
			//if no table was specfied, use the name of the relation
			if (is_int($name)) {
				$name = $class;
				$class = ucfirst(static::singularize($name));
			}
			if (Table::$classPrefix) {
				$class = Table::$classPrefix . $class;
			}
			$relations[$name] = $class;
		}
		return $relations;
	}

	/**
	 * Check if a relation exists
	 * 
	 * @param string $name
	 * @return string|boolean
	 */
	public static function isRelated($name) {
		if (in_array($name, static::getHasOneRelations(true))) {
			return static::HAS_ONE;
		}
		if (in_array($name, static::getHasManyRelations(true))) {
			return static::HAS_MANY;
		}
		if (in_array($name, static::getManyManyRelations(true))) {
			return static::MANY_MANY;
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
			$relations = array_merge(static::getHasOneRelations(), static::getHasManyRelations(), static::getManyManyRelations());
		}

		if ($keys) {
			return array_keys($relations);
		}

		return $relations;
	}

	/**
	 * Get has-one relations
	 * @param bool $keys
	 * @return array
	 */
	public static function getHasOneRelations($keys = false) {
		static $relations = null;

		if ($relations === null) {
			$relations = static::buildRelationsArray(static::$hasOne, static::HAS_ONE);
		}

		if ($keys) {
			return array_keys($relations);
		}

		return $relations;
	}

	/**
	 * Get has-many relations
	 * @param bool $keys
	 * @return array
	 */
	public static function getHasManyRelations($keys = false) {
		static $relations = null;

		if ($relations === null) {
			$relations = static::buildRelationsArray(static::$hasMany, static::HAS_MANY);
		}

		if ($keys) {
			return array_keys($relations);
		}

		return $relations;
	}

	/**
	 * Get many-many relations
	 * @param bool $keys
	 * @return array
	 */
	public static function getManyManyRelations($keys = false) {
		static $relations = null;

		if ($relations === null) {
			$relations = static::buildRelationsArray(static::$manyMany, static::MANY_MANY);
		}

		if ($keys) {
			return array_keys($relations);
		}

		return $relations;
	}

	/**
	 * Get all extra fields that could be defined through a manyMany relation
	 * 
	 * @return array
	 */
	public static function getManyExtraFields($relation) {
		$extra = null;

		if ($extra === null) {
			$extra = array();
			foreach (static::$manyManyExtra as $relation => $fields) {
				$extra[$relation] = static::buildFieldsArray($fields);
			}
		}

		if (isset($extra[$relation])) {
			return $extra[$relation];
		}
		return array();
	}

	/**
	 * Get many-many primary keys
	 * @param string $class
	 * @return array
	 */
	public static function getManyManyKeys($class) {
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
			$fields = static::buildFieldsArray(static::$fields);

			//make sure has-one fields exist
			$hasOneFields = static::getHasOneRelations();
			foreach ($hasOneFields as $name => $class) {
				$field = $class::getForForeignKey($name);
				if (!isset($fields[$field])) {
					//TODO: support other types of primary keys
					$fields[$field] = 'INT';
				}
			}

			//add extensions fields
			foreach (static::getTraits() as $t => $name) {
				$p = 'fields' . $name;
				if (property_exists($t, $p)) {
					$fields = array_merge($fields, $t::$$p);
				}
			}
		}

		if ($keys) {
			return array_keys($fields);
		}

		return $fields;
	}

	/**
	 * Build fields array from definition
	 * 
	 * @param array $arr
	 * @return array
	 */
	protected static function buildFieldsArray($arr) {
		$fields = array();
		foreach ($arr as $name => $type) {
			if (is_int($name)) {
				$name = $type;
				$type = '';
			}
			if (empty($type)) {
				$type = static::getPdo()->guessType($name);
			}
			$fields[$name] = $type;
		}
		return $fields;
	}

	public function getId($mustExist = false) {
		$pkField = static::getPrimaryKey();
		$value = $this->$pkField;
		if (empty($value) && $mustExist) {
			throw new Exception('This record does not have an id yet in ' . get_called_class());
		}
		return $value;
	}

	public function getOriginal() {
		return $this->original;
	}

	public static function getPrimaryKey() {
		$pkFields = static::getPrimaryKeys();
		if (count($pkFields) !== 1) {
			throw new Exception('This method only support table with one primary key in ' . get_called_class());
		}

		return $pkFields[0];
	}

	/**
	 * Get primary keys
	 * @staticvar array $pkFields
	 * @param bool $asArray
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
	public static function getForForeignKey($name = null) {
		$rel = static::getTableName();
		if ($name) {
			$rel = strtolower($name);
		}
		return $rel . '_' . static::getPrimaryKey();
	}

	/**
	 * Get foreign keys based on naming conventions. No query is done on the db.
	 * The foreign key fields must follow the class_field or [rel]_field convention
	 * @staticvar array $fkFields
	 * @return array
	 */
	public static function getForeignKeys() {
		static $fkFields;

		if ($fkFields === null) {
			$fkFields = array();
			$fields = static::getHasOneRelations();
			foreach ($fields as $relation => $class) {
				if (is_int($relation)) {
					$relation = $class;
				}
				$pk = $class::getPrimaryKey();
				$field = strtolower($relation) . '_' . $pk;
				$table = $class::getTableName();
				$fkFields[$field] = $table . '(' . $pk . ')';
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

	public static function syncTable() {
		$pdo = static::getPdo();
		$table = static::getTableName();
		try {
			$res = $pdo->query("SELECT 1 FROM " . $table);
			$exists = true;
		} catch (PdoException $e) {
			$exists = false;
		}
		$fieldsDef = static::getFields(false);
		$fields = array_keys($fieldsDef);

		if ($exists) {
			$cols = array_keys($pdo->listColumns($table));

			$removeFields = array_diff($cols, $fields);
			$addFields = array_diff($fields, $cols);

			if (empty($removeFields) && empty($addFields)) {
				return;
			}

			return $pdo->alterTable($table, $addFields, $removeFields);
		} else {
			$pkFields = static::getPrimaryKeys();
			$fkFields = static::getForeignKeys();
			return $pdo->createTable($table, $fields, $pkFields, $fkFields);
		}
	}

	public static function onBeforeSelect(&$where, &$orderBy, &$limit, &$fields, &$params) {
		
	}

	public static function onAfterSelect(&$data) {
		
	}

	public static function onBeforeInsert(&$data, &$params = null) {
		
	}

	public static function onAfterInsert(&$res, $data, $params = null) {
		
	}

	public static function onBeforeUpdate(&$data, &$where = null, &$params = null) {
		
	}

	public static function onAfterUpdate(&$res, $data, $where = null, $params = null) {
		
	}

	public static function onBeforeDelete(&$data, &$where = null, &$params = null) {
		
	}

	public static function onAfterDelete(&$res, $data, $where = null, $params = null) {
		
	}

	protected function q() {
		return static::query();
	}

	protected static function query() {
		$q = new Query(static::getPdo());
		$q->from(static::getTableName())->fields(static::getTableName() . '.*')->fetchAs(get_called_class());
		return $q;
	}

	/* array access */

	public function offsetSet($offset, $value) {
		$this->setField($offset, $value);
	}

	public function offsetExists($offset) {
		return $this->hasField($offset);
	}

	public function offsetUnset($offset) {
		if ($this->hasField($offset)) {
			$this->setField($offset, null);
		}
	}

	public function offsetGet($offset) {
		return $this->getField($offset);
	}
	
	/* iterator */
	
	public function rewind()
    {
        reset($this->data);
    }
  
    public function current()
    {
        $var = current($this->data);
		return $var;
    }
  
    public function key() 
    {
        $var = key($this->data);
        return $var;
    }
  
    public function next() 
    {
        $var = next($this->data);
        return $var;
    }
  
    public function valid()
    {
        $key = key($this->data);
        $var = ($key !== null && $key !== false);
        return $var;
    }
}
