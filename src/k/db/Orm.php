<?php

namespace k\db;

use \ReflectionClass;
use \Exception;
use \InvalidArgumentException;

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
class Orm extends \k\data\Model {

	const HAS_ONE = 'hasOne';
	const HAS_MANY = 'hasMany';
	const MANY_MANY = 'manyMany';

	/**
	 * Store original field data
	 * 
	 * @var array
	 */
	protected $_original = null;

	/**
	 * Cache resolved objects for the current instance
	 * to avoid querying the database
	 * 
	 * @var array
	 */
	protected $_cache = array();

	/**
	 * Query used to fetch the model
	 * 
	 * @var Query
	 */
	protected $_query;

	/**
	 * Map model types to sql
	 * 
	 * @var array
	 */
	protected static $_typesMap = [
		self::IP => 'VARCHAR(45)',
		self::EMAIL => 'VARCHAR(255)',
		self::URL => 'VARCHAR(255)',
		self::DIGITS => 'INT',
		self::NUMBER => 'FLOAT',
		self::NUMERIC => 'FLOAT',
		self::INTEGER => 'INT',
		self::SLUG => 'VARCHAR(255)',
		self::ALPHA => 'VARCHAR(255)',
		self::ALPHANUM => 'VARCHAR(255)',
		self::DATE_ISO => 'DATETIME',
		self::DATE => 'DATE',
		self::TIME => 'TIME',
		self::PHONE => 'VARCHAR',
		self::LUHN => 'VARCHAR(255)',
	];

	/**
	 * Connection name
	 * @var string
	 */
	protected static $_connection = 'default';

	/**
	 * Store has-one relations.
	 * 
	 * Relations are defined as an array like ['Name','OtherName' => 'Class']
	 * Conventions is that you use singular name
	 * 
	 * @var array 
	 */
	protected static $_hasOne = [];

	/**
	 * Store has-many relations.
	 * 
	 * Relations are defined as an array like ['Name','OtherName' => 'Class']
	 * Conventions is that you use plural name
	 * 
	 * @var array 
	 */
	protected static $_hasMany = [];

	/**
	 * Store has-one relations.
	 * 
	 * Relations are defined as an array like ['Name','OtherName' => 'Class']
	 * Conventions is that you use plural name
	 * 
	 * Define extra fields through an array instead of just a class like this
	 * ['OtherName' => ['Class', 'some', 'extra', 'field' => 'VARCHAR']]
	 * 
	 * @var array 
	 */
	protected static $_manyMany = [];

	/**
	 * Base folder to use for file storage
	 * 
	 * @var string
	 */
	protected static $_storage;

	/**
	 * Prefix to add/remove before the model objects before mapping them to tables
	 * Can be a prefix like Model_ or a namespace like models\\
	 * 
	 * @var string
	 */
	protected static $_classPrefix = 'Model_';

	/* --- class methods --- */

	/**
	 * Create a fake record in the db
	 * 
	 * For more advanced fake records, use a library like Faker
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
	 * Get base storage folder
	 * 
	 * @param bool $create
	 * @return string
	 */
	public static function getBaseFolder($create = false) {
		$folder = static::getStorage() . '/' . static::getTableName();
		if ($create && !is_dir($folder)) {
			mkdir($folder);
		}
		return $folder;
	}

	/**
	 * Inject records for this table in an array of records
	 * 
	 * @param array $array
	 * @param string $relation
	 */
	public static function inject(array &$array, $relation = null) {
		if (empty($array)) {
			return;
		}
		$first = $array[0];
		$class = get_called_class();
		$modelName = $class::getModelName();
		$injectClass = get_class($first);
		$type = $injectClass::isRelated($relation);
		if (!$type) {
			return;
		}
		$table = static::getTable();
		$pk = static::getPrimaryKey();
		$fk = $injectClass::getForForeignKey($relation);
		$classFk = $recordColumn = $column = static::getForForeignKey($relation);

		if ($type == self::HAS_MANY || $type == self::MANY_MANY) {
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
		switch ($type) {
			case self::HAS_ONE:
				$injected = static::query()->where($pk, $ids)->orderBy($pk . ' ASC')->fetchAll();
				$byId = array();
				foreach ($injected as $record) {
					$byId[$record->$pk] = $record;
				}
				foreach ($array as $record) {
					$key = $record->$recordColumn;
					if (isset($byId[$key])) {
						$o = $byId[$key];
					} else {
						//if we inject something, make sure we have at least a null class
						$o = new $class;
						$o->$pk = $key;
					}
					$record->setCache($relation, $o);
				}
				break;
			case self::HAS_MANY:
				$injected = static::query()->where($fk, $ids)->orderBy($pk . ' ASC')->fetchAll();
				foreach ($array as $record) {
					$id = $record->getId();
					$arr = array();
					foreach ($injected as $i) {
						if ($i->$column == $record->getId()) {
							$arr[] = $i;
						}
					}
					$record->setCache($table, $arr);
				}
				break;
			case self::MANY_MANY:
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
					$record->setCache($table, $arr);
				}
				break;
		}
	}

	/**
	 * Do not try to insert or update fields that don't exist
	 * 
	 * @param array $data
	 * @return array
	 */
	protected static function filterData($data) {
		if (!is_array($data)) {
			return $data;
		}
		$fields = static::getFields();
		foreach ($data as $k => $v) {
			if (!in_array($k, $fields)) {
				unset($data[$k]);
			}
		}
		return $data;
	}

	/**
	 * Insert data
	 * 
	 * @param array $data
	 * @return int The id of the record
	 */
	public static function insert($data) {
		$data = static::filterData($data);
		$table = static::getTableName();
		return static::getPdo()->insert($table, $data);
	}

	/**
	 * Update data
	 * 
	 * @param array $data
	 * @param array|string $where
	 * @param array $params
	 * @return bool
	 */
	public static function update($data, $where = null, $params = array()) {
		$data = static::filterData($data);
		$table = static::getTableName();
		return static::getPdo()->update($table, $data, $where, $params);
	}

	/**
	 * Delete data
	 * 
	 * @param array|string $where
	 * @param array $params
	 * @return bool
	 */
	public static function delete($where, $params = array()) {
		$table = static::getTableName();
		return static::getPdo()->delete($table, $where, $params);
	}

	/**
	 * Get pdo instance for this Orm
	 * 
	 * The pdo is based on the $_connection variable
	 * 
	 * @return Pdo
	 */
	public static function getPdo() {
		return Pdo::get(static::getConnection());
	}

	/**
	 * Get connection
	 * 
	 * @return string
	 */
	public static function getConnection() {
		return static::$_connection;
	}

	/**
	 * Set connection
	 * 
	 * @param string $connection
	 */
	public static function setConnection($connection) {
		static::$_connection = $connection;
	}

	/**
	 * Get storage folder
	 * 
	 * @return string
	 */
	public static function getStorage() {
		return static::$_storage;
	}

	/**
	 * Set storage folder
	 * 
	 * @param string $storage
	 */
	public static function setStorage($storage) {
		static::$_storage = $storage;
	}

	/**
	 * Get class prefix
	 * 
	 * @return string
	 */
	public static function getClassPrefix() {
		return static::$_classPrefix;
	}

	/**
	 * Set class prefix
	 * 
	 * @param string $prefix
	 */
	public static function setClassPrefix($prefix) {
		static::$_classPrefix = $prefix;
	}

	/**
	 * Convert {PREFIX}MyClass to my_class
	 * 
	 * @return string
	 */
	public static function getTableName() {
		return strtolower(static::getModelName());
	}

	/**
	 * Get model name without the prefix
	 * 
	 * @return string
	 */
	public static function getModelName() {
		$name = str_replace(static::getClassPrefix(), '', get_called_class());
		return $name;
	}

	/**
	 * Given a table, find what class is supposed to handle it
	 * 
	 * @param string $table
	 */
	public static function getClassForTable($table = null) {
		if ($table === null) {
			$table = static::getTableName();
		}
		return static::getClassPrefix() . str_replace(' ', '', ucwords(str_replace('_', ' ', $table)));
	}

	/**
	 * Get many table
	 * 
	 * @param string $name
	 * @return string
	 */
	public static function getManyManyTable($name) {
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
	protected static function buildRelationsArray($arr) {
		$relations = array();

		foreach ($arr as $name => $class) {
			//if no table was specfied, use the name of the relation
			if (is_int($name)) {
				$name = $class;
				$class = ucfirst(static::singularize($name));
			}
			if (static::getClassPrefix()) {
				$class = static::getClassPrefix() . $class;
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
			return self::HAS_ONE;
		}
		if (in_array($name, static::getHasManyRelations(true))) {
			return self::HAS_MANY;
		}
		if (in_array($name, static::getManyManyRelations(true))) {
			return self::MANY_MANY;
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
			$relations = static::buildRelationsArray(static::$_hasOne);
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
			$relations = static::buildRelationsArray(static::$_hasMany);
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
			$relations = static::buildRelationsArray(static::$_manyMany);
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
			foreach (static::$_manyManyExtra as $relation => $fields) {
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
	 * Get fields. By default only return keys.
	 * 
	 * @staticvar array $fields
	 * @return array
	 */
	public static function getFields($keys = true) {
		static $fields;

		if ($fields === null) {
			$fields = static::buildFieldsArray(static::getDeclaredPublicProperties());

			//make sure has-one fields exist
			$hasOneFields = static::getHasOneRelations();
			foreach ($hasOneFields as $name => $class) {
				$field = $class::getForForeignKey($name);
				if (!isset($fields[$field])) {
					$types = $class::getFields(false);
					$fields[$field] = 'INT';
//					$fields[$field] = $types[$field];
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
	 * Get an array of fields => rules
	 * 
	 * @return array
	 */
	public static function getRules() {
		$rules = parent::getRules();

		//validations rules based on type
		$nameRules = array(
			//length
			'zipcode' => ['rangelength' => [4, 20]],
			'lang_code|country_code' => ['rangelength' => '[2,2]'],
			//numbers
			'id' => 'digits',
			'_?price$' => 'number',
			'_?(id|count|quantity|level|percent|number|sort_order|perms|permissions|day)$' => 'digits',
			'_?(lat|lng|lon|latitude|longitude)$' => 'digits',
			//dates
			'_?(datetime|at)$' => 'dateIso',
			'_?(date|birthdate|birthday)$' => ['regexp' => '^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$'],
			'_?time$' => ['regexp' => '^(?:(?:([01]?\d|2[0-3]):)?([0-5]?\d):)?([0-5]?\d)$'],
			//types
			'_?email$' => 'email',
			'_?url$' => 'url',
		);

		$fields = static::getFields(false);
		foreach ($fields as $field => $type) {
			if (isset($rules[$field])) {
				continue;
			}
			//guess by name
			foreach ($nameRules as $r => $v) {
				if (preg_match('/' . $r . '/', $field)) {
					$rules[$field] = $v;
					break;
				}
			}
		}

		return $rules;
	}

	/**
	 * Build fields array from definition
	 * 
	 * @param array $arr
	 * @return array
	 */
	protected static function buildFieldsArray($arr) {
		$fields = array();
		$types = static::getTypes();
		foreach ($arr as $name => $type) {
			if (is_int($name)) {
				$name = $type;
				$type = '';
			}
			if (empty($type)) {
				if (isset($types[$name]) && isset(static::$_typesMap[$name])) {
					$type = static::$_typesMap[$name];
				} else {
					$type = static::getPdo()->guessType($name);
				}
			}
			$fields[$name] = $type;
		}
		return $fields;
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
		if ($name && $name != static::getModelName()) {
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

	public static function createTable($execute = false) {
		$pdo = static::getPdo();
		$table = static::getTableName();
		$fields = static::getFields();
		$pkFields = static::getPrimaryKeys();
		$fkFields = static::getForeignKeys();
		return $pdo->createTable($table, $fields, $pkFields, $fkFields, $execute);
	}

	public static function alterTable($execute = false) {
		$pdo = static::getPdo();
		$table = static::getTableName();
		$fields = static::getFields();
		$pkFields = static::getPrimaryKeys();
		$fkFields = static::getForeignKeys();

		$cols = array_keys($pdo->listColumns($table));

		$removeFields = array_diff($cols, $fields);
		$addFields = array_diff($fields, $cols);

		if (empty($removeFields) && empty($addFields)) {
			return;
		}

		return $pdo->alterTable($table, $addFields, $removeFields, $execute);
	}

	/**
	 * Create or update table
	 * 
	 * @param boolean $execute
	 * @return string
	 */
	public static function syncTable($execute = true) {
		$pdo = static::getPdo();
		$table = static::getTableName();
		try {
			$res = $pdo->query("SELECT 1 FROM " . $table);
			$exists = true;
		} catch (PdoException $e) {
			$exists = false;
		}

		if ($exists) {
			return static::alterTable($execute);
		} else {
			return static::createTable($execute);
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

	/* --- instance methods --- */

	/**
	 * Get a property as a class that has a create method
	 * 
	 * @param string $name
	 * @param string $class
	 * @return Object
	 */
	public function getAs($name, $class) {
		if (isset($this->_cache[$name])) {
			return $this->_cache[$name];
		}
		$this->_cache[$name] = $class::create($this->getField($name));
		return $this->_cache[$name];
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
		if ($this->_query) {
			$this->_query->prefetch($name, $class);
			//record will be cached by prefetch if exists
			$data = $this->getCache($name);
			if (!$data) {
				$data = new $class;
			}
			return $data;
		}
		$data = null;
		switch ($type) {
			case static::HAS_ONE:
				$pkField = $class::getPrimaryKey();
				$field = $class::getForForeignKey($name);
				$value = $this->$field;
				$data = null;
				if ($value) {
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
		$this->setCache($name, $data);
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
		$type = static::isRelated($class, $relation);
		switch ($type) {
			case 'hasOne':
				$field = $class::getForForeignKey($relation);
				$this->$field = $o->getId();
				$this->_cache[$field] = $o;
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
		$type = static::isRelated($class, $relation);

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

	protected function onPreSave() {
		//implement in subclass, return false to cancel
	}

	protected function onPostSave() {
		//implement in subclass
	}

	protected function changedFields() {
		$changed = array();
		foreach ($this->getOriginal() as $k => $v) {
			if ($this->$k != $v) {
				$changed[$k] = $this->$k;
			}
		}
		return $changed;
	}

	/**
	 * Save the record
	 * @return boolean
	 */
	public function save() {
		//save cached objects too
		foreach ($this->getCache() as $name => $o) {
			if (is_object($o)) {
				if (is_subclass_of($o, '\\k\\db\\Orm')) {
					$o->save();
					$class = get_class($o);
					$field = $class::getForForeignKey();
					$this->$field = $o->getId();
				}
			}
		}

		//prepare data
		$exists = $this->exists();
		if ($exists) {
			$this->saveData = $this->changedFields();
		} else {
			$this->saveData = array_filter($this->toArray());
		}

		//call hooks, cancel if return false
		foreach (static::getTraits() as $t => $name) {
			$p = 'onPreSave' . $name;
			if (method_exists($t, $p)) {
				$res = $this->$p();
				if ($res === false) {
					return false;
				}
			}
		}
		$res = $this->onPreSave();
		if ($res === false) {
			return false;
		}

		if (empty($this->saveData)) {
			$this->saveStatus = false;
			return $this->saveStatus;
		}

		if ($exists) {
			$this->saveStatus = static::update($this->saveData, $this->pkAsArray());
		} else {
			$this->saveStatus = static::insert($this->saveData);
			if ($this->saveStatus && $this->hasField('id')) {
				$this->id = $this->saveStatus;
			}
		}

		$this->onPostSave();

		return $this->saveStatus;
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
		if (!$pkFields) {
			return $where;
		}
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

	public function onPreRemove() {
		//implement in subclass, return false to cancel
	}

	public function onPostRemove($result) {
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
			$this->onPostRemove($res);
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
	 * Shortcut for getRelated
	 * @param string $name
	 * @param array $arguments
	 * @return array
	 */
	public function __call($name, $arguments) {
		return $this->getRelated($name);
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

	public function getCache($name = null, $default = null) {
		if (!$name) {
			return $this->_cache;
		}
		if (isset($this->_cache[$name])) {
			return $this->_cache[$name];
		}
		return $default;
	}

	public function setCache($name = null, $value = null) {
		$this->_cache[$name] = $value;
		return $this;
	}

	/**
	 * Get a property or a virtual property.
	 * 
	 * $o->get('firstname') -> $this->firstname
	 * $o->get('virtual') => get_virtual
	 * $o->get('Employee') => getRelated('Employee')
	 * $o->get('Employee.name') => getRelated('Employee')->name
	 * 
	 * @param string $name
	 * @return string
	 */
	public function get($name) {
		//relation
		if (strpos($name, '.') !== false) {
			$parts = explode('.', $name);
			$part = array_shift($parts);
			if (static::isRelated($part)) {
				$o = $this->getRelated($part);
				if (!$o) {
					return null;
				}
				return $o->get(implode('.', $parts));
			}
		}
		return parent::get($name);
	}

	/**
	 * Set a property or a virtual property
	 * 
	 * @param string $name
	 * @param mixed $value
	 * @return \k\db\Orm
	 */
	public function set($name, $value) {
		//object conversion
		if (is_object($value)) {
			switch ($value) {
				case ($value instanceof Orm):
					$object = $value;
					if ($object->exists()) {
						$value = $value->getId();
					}
					break;
				case ($value instanceof \DateTime):
					$value = $value->format('Y-m-d H:i:s');
					break;
				case ($value instanceof \k\File):
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
		//we don't store array for orm
		if (is_array($value)) {
			$value = implode(',', $value);
		}

		return parent::set($name, $value);

		//relation
//			if (in_array($name, static::getManyExtraFields())) {
//				$this->$name = $value;
//			} else {
//		$name = str_replace('_id', '', $name); //we might try to set this because of a join
//		if (static::isRelated($name) && isset($object)) {
//			return $this->addRelated($object);
//		}
//			}
		return false;
	}

	/**
	 * Additionnal checks on relations
	 * 
	 * @param string $name
	 * @return boolean
	 */
	public function has($name) {
		if (parent::has($name)) {
			return true;
		}
		//if we have a dot through array access, suppose we know what we do
		if (strpos($name, '.') !== false) {
			return true;
		}
		if (static::isRelated($name)) {
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
			if (isset($data[static::getTableName()])) {
				$data = $data[static::getTableName()];
			}
		}
		foreach ($data as $k => $v) {
			if ($this->hasField($k)) {
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

	public function getParentQuery() {
		return $this->_query;
	}

	public function setParentQuery(Query $query) {
		$this->_query = $query;
		return $this;
	}

	public function getId($mustExist = false) {
		$pkField = static::getPrimaryKey();
		$value = $this->$pkField;
		if (empty($value) && $mustExist) {
			throw new Exception('This record does not have an id yet in ' . get_called_class());
		}
		return $value;
	}

	public function getTitle() {
		$potentials = ['title', 'name', 'username'];
		foreach ($potentials as $p) {
			if (property_exists($this, $p)) {
				return $this->$p;
			}
		}
		return $this->getId();
	}

	public function getOriginal() {
		return $this->_original;
	}

	/* --- factories --- */

	/**
	 * Alias getTable
	 * @return \k\db\Table
	 */
	public function t($name = null) {
		return static::getTable($name);
	}

	/**
	 * @return \k\db\Table
	 */
	public static function getTable($name = null) {
		if ($name === null) {
			$name = static::getTableName();
		}
		return static::getPdo()->t($name);
	}

	/**
	 * Alias getQuery
	 * @return \k\db\Query
	 */
	public static function q() {
		return static::getQuery();
	}

	/**
	 * @return \k\db\Query
	 */
	public static function getQuery() {
		return static::getDefaultQuery();
	}

	/**
	 * @return \k\db\Query
	 */
	public static function getDefaultQuery() {
		$q = new Query(static::getPdo());
		$q->from(static::getTableName())->fields(static::getTableName() . '.*')->fetchAs(get_called_class());
		return $q;
	}

}
