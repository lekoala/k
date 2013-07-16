<?php

namespace k\db\orm;

/**
 */
trait Type {

	protected static $_typeCache;
	
	public static function getTableType() {
		return static::getTableName() . 'type';
	}

	public static function createTableType($execute = true) {
		$table = static::getTableName();
		$ttable = static::getTableType();
		$fields = array(
			'id',
			'name'
		);
		//TODO: add extra fields
		return static::getPdo()->createTable($ttable, $fields, array(), null, $execute);
	}

	public static function dropTableType() {
		return static::getPdo()->dropTable(static::getTableType());
	}

	/**
	 * Does the record have the info
	 * 
	 * @param string $name
	 * @param mixed $value
	 * @param boolean $cache
	 * @return boolean
	 */
	public function hasType($name = null, $cache = false) {
		if($cache) {
			if($this->_infoCache !== null) {
				return in_array($name, static::$_typeCache);
			}
			return false;
		}
		$table = static::getTable();
		$ttable = static::getTableType();
		$where = array('name' => $name);
		return static::getPdo()->count($ttable, $where);
	}

	/**
	 * Add an info
	 * 
	 * @param string $name
	 * @param mixed $value
	 * @return boolean
	 */
	public function addType($name) {
		$ttable = static::getTableType();
		if (is_array($name)) {
			foreach ($name as $n) {
				$this->addType($n);
			}
		}
		if (!$this->hasType($name)) {
			if(static::$_typeCache === null) {
				static::$_typeCache = [];
			}
			static::$_typeCache[] = $name;
			return static::getPdo()->insert($ttable, array('name' => $name));
		}
		return true;
	}

	/**
	 * Remove an info
	 * 
	 * @param string $name
	 * @param mixed $value
	 * @return boolean
	 */
	public function removeType($name = null) {
		$ttable = static::getTableType();
		if (is_array($name)) {
			foreach ($name as $n) {
				$this->removeType($n);
			}
		}
		$where = array('name' => $name);
		return static::getPdo()->delete($ttable, $where);
	}

	/**
	 * Get all associated infos. Cached by default
	 * 
	 * @param string $name
	 * @param boolean $cache
	 * @return array
	 */
	public function getType($name = null, $cache = true) {
		
	}

}