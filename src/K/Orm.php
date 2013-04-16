<?php

namespace K;

use K\Pdo;
use ReflectionClass;

/**
 * Description of Orm
 *
 * @author tportelange
 */
class Orm {

	use TConfigure;

	protected $_original = array();
	protected $_cache = array();

	/**
	 * @var Pdo
	 */
	protected static $pdo;

	/**
	 * Store many-many relations like array('Class' => array('my','extra','field'))
	 * @var array
	 */
	protected static $manyMany = array();

	/**
	 * @var string
	 */
	protected static $storage;

	/**
	 * Define validation rules. You can also define custom methods like validateFieldName()
	 * @var array
	 */
	protected static $validation = array(
		'id' => 'int'
	);

	public function __construct($where = null) {
		if ($where) {
			$where = static::detectPrimaryKeys($where);
			$params = array();
			$stmt = static::getPdo()->selectStmt(static::getTable(), $where, null, null, '*', $params);
			$stmt->setFetchMode(\PDO::FETCH_INTO, $this);
			$result = $stmt->execute($params);
			if (!$stmt->fetch()) {
				throw new Exception('Failed to load record ' . json_encode(array_values($where)) . ' of class ' . get_called_class());
			}
		}
		$this->_original = $this->toArray();
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
			} elseif (strpos($type, 'DECIMAL') !== false
					|| strpos($type, 'FLOAT') !== false) {
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

	public function __get($name) {
		return $this->getProperty($name);
	}

	public function __set($name, $value) {
		return $this->setProperty($name, $value);
	}

	/**
	 * Get a property or a virtual property
	 * @param string $name
	 * @return string
	 */
	public function getProperty($name) {
		//property or extra field property
		if (property_exists($this, $name)) {
			return $this->$name;
		}
		//virtal properties
		elseif (method_exists($this, $name)) {
			return $this->$name();
		}
		//relation
		else {
			$o = $this->getRelated($name);
			if ($o) {
				return $o;
			}
		}
		return '';
	}

	/**
	 * Get a property as a class that has a create method
	 * @param string $name
	 * @param string $class
	 * @return Object
	 */
	public function getAs($name, $class) {
		if(isset($this->_cache[$name])) {
			return $this->_cache[$name];
		}
		$this->_cache[$name] = $class::create($this->getProperty($name));
		return $this->_cache[$name];
	}

	/**
	 * Get a property as a date
	 * @param string $name
	 * @return K\Date
	 */
	public function getAsDate($name) {
		return static::getAs($name, 'Date');
	}

	/**
	 * Get a property as file. File is stored in base_folder/property_value
	 * @param string $name
	 * @return K\File
	 */
	public function getAsFile($name) {
		$path = static::getBaseFolder() . '/' . $this->getProperty($name);
		return static::getAs($path, 'File');
	}

	/**
	 * Get base storage folder
	 * @param bool $create
	 * @return string
	 */
	public static function getBaseFolder($create = true) {
		$folder = static::$storage . '/' . static::getTable();
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
	public function getFolder($create = true) {
		$folder = static::getBaseFolder() . '/' . $this->getId();
		if ($create && !is_dir($folder)) {
			mkdir($folder);
		}
		return $folder;
	}

	/**
	 * Validate a value for a given field
	 * @param string $name
	 * @param mixed $value
	 */
	public static function validate($name, $value) {
		if (isset(static::$validation[$name])) {
			switch (static::$validation[$name]) {
				case 'int' :
					if (is_numeric($value) || ctype_digit($value)) {
						return true;
					}
					break;
				default :
					throw new Exception('Undefined validation rule ' . $name);
			}
			throw new Exception('Value ' . $value . ' must validate rule ' . $name);
		}
		$method = 'validate' . str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
		if (method_exists(get_called_class(), $method)) {
			if (!static::$method($value)) {
				throw new Exception('Value ' . $value . ' must validate rule ' . $method);
			}
		}
	}

	/**
	 * Set a property or a virtual property
	 * @param string $name
	 * @param mixed $value
	 * @return \K\Orm
	 */
	public function setProperty($name, $value) {
		if (is_object($value)) {
			if (is_subclass_of($value, 'K\Orm')) {
				$object = $value;
				if($object->exists()) {
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
		static::validate($name, $value);
		$method = '_' . $name;
		if (property_exists($this, $name)) {
			$this->$name = $value;
		}
		//virtual property setter
		elseif (method_exists($this, $method)) {
			$this->$method($value);
		}
		//relation
		else {
			if (in_array($name, static::getManyExtraFields())) {
				$this->$name = $value;
			} else {
				$name = str_replace('_id', '', $name); //we might try to set this because of a join
				if ($this->isRelated($name) && isset($object)) {
					$this->addRelated($object);
				}
			}
		}
		return $this;
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

		if ($type == 'has-many' || $type == 'many-many') {
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
			case 'has-one':
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
					$record->_cache[$column] = $o;
				}
				break;
			case 'has-many':
				$injected = static::get()->where($fk, $ids)->orderBy($pk . ' ASC')->fetchAll();
				foreach ($array as $record) {
					$id = $record->getId();
					$arr = array();
					foreach ($injected as $i) {
						if ($i->$column == $record->getId()) {
							$arr[] = $i;
						}
					}
					$record->_cache[$table] = $arr;
				}
				break;
			case 'many-many':
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
					$record->_cache[$table] = $arr;
				}
				break;
		}
	}

	/**
	 * Find the relation between to class or table
	 * @param string $class
	 * @return string
	 */
	public static function isRelated($class, $relation = '') {
		$class = ucfirst(str_replace(' ', '', ucwords(preg_replace('/[^A-Z^a-z^0-9]+/', ' ', $class))));

		//Exit early to avoid unnecessary checks
		if (!class_exists($class, false) || !is_subclass_of($class, 'K\Orm')) {
			return false;
		}

		$name = $class::getForForeignKey($relation);
		$fkFields = static::getForeignKeys();
		foreach ($fkFields as $fk) {
			if ($name == $fk['name'] && $fk['relation'] == $relation) {
				return 'has-one';
			}
		}

		if (in_array($class, static::getManyRelations(true))) {
			return 'many-many';
		}

		$field = static::getForForeignKey($relation);
		if (property_exists($class, $field)) {
			return 'has-many';
		}

		return false;
	}

	/**
	 * Get related objects
	 * @param string $class
	 * @param string $relation
	 * @return array
	 */
	public function getRelated($class, $relation = '') {
		$class = ucfirst(str_replace(' ', '', ucwords(preg_replace('/[^A-Z^a-z^0-9]+/', ' ', $class))));
		$type = $this->isRelated($class, $relation);
		switch ($type) {
			case 'has-one' :
				$name = $class::getForForeignKey($relation);
				$fkFields = static::getForeignKeys();
				foreach ($fkFields as $fk) {
					if ($name == $fk['name'] && $fk['relation'] == $relation) {
						if (isset($this->_cache[$fk['name']])) {
							return $this->_cache[$fk['name']];
						}
						$class = ucfirst(str_replace(' ', '', ucwords(preg_replace('/[^A-Z^a-z^0-9]+/', ' ', $name))));
						if (class_exists($class, false) && is_subclass_of($class, 'K\Orm')) {
							$pk = $this->$fk['name'];
							if (empty($pk)) {
								$this->_cache[$fk['name']] = new $class();
							} else {
								$this->_cache[$fk['name']] = new $class($pk);
							}
							return $this->_cache[$fk['name']];
						}
					}
				}
				return false;
				break;
			case 'has-many' :
				$table = $class::getTable();
				if (isset($this->_cache[$table])) {
					return $this->_cache[$table];
				}

				$q = $class::get()
						->where(static::getForForeignKey(), $this->id);
				$this->_cache[$table] = $q->fetchAll();
				return $this->_cache[$table];
				break;
			case 'many-many' :
				$table = $class::getTable();
				if (isset($this->_cache[$table])) {
					return $this->_cache[$table];
				}
				$q = $class::get()
						->fields($table . '.*')
						->innerJoin(static::getManyTable($class), $class::getTable() . '.' . $class::getPrimaryKey() . ' = ' . static::getManyTable($class) . '.' . $class::getForForeignKey())
						->where(static::getForForeignKey(), $this->id);
				$this->_cache[$table] = $q->fetchAll();
				return $this->_cache[$table];
				break;
		}
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
			case 'has-one':
				$field = $class::getForForeignKey($relation);
				$this->$field = $o->getId();
				$this->_cache[$field] = $o;
				return $this->save();
				break;
			case 'has-many':
				$table = $class::getTable();
				$field = static::getForForeignKey();
				$o->$field = $this->getId();
				$this->clearCache($table);
				return $o->save();
				break;
			case 'many-many':
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
			case 'has-one':
				$field = $class::getForForeignKey($relation);
				$this->$field = null;
				return $this->save();
				break;
			case 'has-many':
				$field = static::getForForeignKey();
				$o->$field = null;
				return $o->save();
				break;
			case 'many-many':
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
		$this->_cache = array();
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

	public function onPreSave() {
		//implement in subclass
	}

	public function onPostSave() {
		//implement in subclass
	}

	/**
	 * Save the record
	 * @return boolean
	 */
	public function save() {
		$res = $this->onPreSave();
		if ($res === false) {
			return false;
		}

		//save cached objects too
		foreach ($this->_cache as $field => $v) {
			$v->save();
			$this->$field = $v->getId();
		}

		$data = $this->toArray();
		if ($this->exists()) {
			$changed = array();
			foreach ($this->_original as $k => $v) {
				if ($this->$k != $v) {
					$changed[$k] = $this->$k;
				}
			}
			if (empty($changed)) {
				return true;
			}
			$res = static::update($changed, $this->pkAsArray());
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
			if ($res && property_exists($this, 'id')) {
				$this->id = $res;
			}
		}
		$this->onPostSave();
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

	public function __toString() {
		return get_called_class() . json_encode($this->pkAsArray());
	}

	/**
	 * Simple html representation enclosed in a div
	 * @param array|string $fields
	 * @return string
	 */
	public function html($fields = null) {
		if ($fields === null) {
			$fields = static::getFields();
		}
		if (is_string($fields)) {
			$fields = explode(',', $fields);
			$fields = array_map('trim', $fields);
		}
		$html = '<div class="' . get_called_class() . '">';
		foreach ($fields as $field) {
			$html .= "\n" . '<p class="' . $field . '">' . $this->$field . '</p>';
		}
		$html .= "\n</div>";
		return $html;
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
					$value = $obj->getProperty($field);
				}
			} else {
				$value = $this->getProperty($field);
			}
			$html .= "<td>" . $value . "</td>\n";
		}
		return $html;
	}

	/**
	 * Export object to an array
	 * @param string|array $virtual Add virtual properties to the array
	 * @param string|array $only Restrict to these fields only
	 * @return array
	 */
	public function toArray($virtual = array(), $only = array()) {
		$fields = static::getFields();
		$data = array();
		if (is_string($only)) {
			$only = explode(',', $only);
		}
		foreach ($fields as $field) {
			if (!empty($only) && !in_array($field, $only)) {
				continue;
			}
			$data[$field] = $this->$field;
		}
		if (is_string($virtual)) {
			$virtual = explode(',', $virtual);
		}
		foreach ($virtual as $v) {
			if (method_exists($this, $v)) {
				$data[$v] = $this->$v();
			}
		}
		return $data;
	}

	/**
	 * @return Pdo
	 */
	public static function getPdo() {
		return static::$pdo;
	}

	/**
	 * Get table
	 * @staticvar string $table
	 * @return string
	 */
	public static function getTable() {
		static $table;

		if (empty($table)) {
			$table = explode('\\', get_called_class());
			$table = end($table);
			$table = strtolower($table);
		}

		return $table;
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
	 * Get many relations
	 * @param bool $keys
	 * @return array
	 */
	public static function getManyRelations($keys = false) {
		static $relations = null;

		if ($relations === null) {
			$relations = array();
			foreach (static::$manyMany as $class => $extraFields) {
				if (is_int($class)) {
					$class = $extraFields;
					$extraFields = '';
				}
				$relations[$class] = $extraFields;
			}
		}

		if ($keys) {
			return array_keys($relations);
		}

		return $relations;
	}

	/**
	 * Get all extra fields that could be defined through a many-many relations
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
	public static function getFields() {
		static $fields;

		if ($fields === null) {
			$fields = array();
			$ref = new ReflectionClass(get_called_class());
			$properties = $ref->getProperties();
			/** @var $property ReflectionProperty */
			foreach ($properties as $property) {
				if ($property->isStatic() || strpos($property->getName(), '_') === 0) {
					continue;
				}
				$fields[] = $property->getName();
			}
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

	public static function getPrimaryKey() {
		$pkFields = static::getPrimaryKeys();

		if (count($pkFields) != 1) {
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
			$fields = static::getFields();
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
	public static function getForForeignKey($relation = null) {
		$pkFields = static::getPrimaryKeys();

		if (count($pkFields) != 1) {
			throw new Exception('This method only support table with one primary key');
		}

		if ($relation) {
			$relation .= '_';
		}

		return static::getTable() . '_' . $relation . $pkFields[0];
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

	/**
	 * Get the fluent query builder
	 * @return K\SqlQuery
	 */
	public static function get() {
		return SqlQuery::create(static::getTable())
						->fetchAs(get_called_class())
		;
	}
	
	/**
	 * Get the fluent query builder with class defaults
	 * @return K\SqlQuery
	 */
	public static function getDefault() {
		return SqlQuery::create(static::getTable())
						->fetchAs(get_called_class())
						->where(static::getDefaultWhere())
						->orderBy(static::getDefaultSort())
		;
	}
	
	/**
	 * Handle direct primary keys as params in where
	 * @param int|array $where
	 * @return type
	 * @throws Exception
	 */
	protected static function detectPrimaryKeys($where) {
		if (empty($where)) {
			return $where;
		}
		$pkFields = static::getPrimaryKeys();
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
	 * Enum list values based on class constants. Constants MUST_LOOK_LIKE_THIS
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
	 * Do not try to insert or update fields that don't exist
	 * @param array $data
	 * @return array
	 */
	public static function filterData($data) {
		$fields = static::getFields();
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
	public static function count($where = null, $params = array()) {
		return static::getPdo()->count(static::getTable(), $where, $params);
	}

	/**
	 * @param string $field
	 * @param string $fields
	 * @return array
	 */
	public static function duplicates($field, $fields = '*') {
		return static::getPdo()->duplicates(static::getTable(), $field, $fields);
	}

	/**
	 * @param string $field
	 * @return int
	 */
	public static function min($field = 'id') {
		return static::getPdo()->min(static::getTable(), $field);
	}

	/**
	 * @param string $field
	 * @return int
	 */
	public static function max($field = 'id') {
		return static::getPdo()->max(static::getTable(), $field);
	}

	/**
	 * @param array|string $where
	 * @param array|string $orderBy
	 * @param array|string $limit
	 * @param array|string $fields
	 * @param array $params
	 * @return array 
	 */
	public static function select($where = array(), $orderBy = 'default', $limit = null, $fields = '*', $params = array()) {
		$where = static::detectPrimaryKeys($where);
		if ($orderBy == 'default') {
			$orderBy = static::getDefaultSort();
		}
		return static::getPdo()->select(static::getTable(), $where, $orderBy, $limit, $fields, $params);
	}

	public static function onInsert(&$data) {
		
	}

	/**
	 * @param array $data
	 * @return int The id of the record
	 */
	public static function insert($data = array()) {
		static::onInsert($data);
		$data = static::filterData($data);
		return static::getPdo()->insert(static::getTable(), $data);
	}

	public static function onUpdate(&$data) {
		
	}

	/**
	 * @param array $data
	 * @param array|string $where
	 * @param array $params
	 * @return bool
	 */
	public static function update($data = array(), $where = null, $params = array()) {
		static::onUpdate($data);
		$data = static::filterData($data);
		$where = static::detectPrimaryKeys($where);

		//detect primary key and use it as where condition
		if (empty($where)) {
			$where = array();
			foreach (static::getPrimaryKeys() as $pk) {
				if (isset($data[$pk])) {
					$where[$pk] = $data[$pk];
					unset($data[$pk]);
				}
			}
		}

		return static::getPdo()->update(static::getTable(), $data, $where, $params);
	}

	/**
	 * @param array|string $where
	 * @param array $params
	 * @return bool
	 */
	public static function delete($where, $params = array()) {
		$where = static::detectPrimaryKeys($where);
		return static::getPdo()->delete(static::getTable(), $where, $params);
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

}
