<?php

namespace k\db\orm;

/**
 * Allows typed records
 * You can specify additional properties in typeFields
 * Type fields are added to the record
 */
trait Type {
	
	public static function getHasOneRelationsType() {
		$class = get_called_class() . 'Type';
		return array($class);
	}

	public static function getTableType() {
		return static::getTable() . 'Type';
	}

	public static function createTableType($execute = true) {
		$table = static::getTable();
		$ttable = static::getTableType();
		$fields = static::getFields();
		$pk = static::getPrimaryKeys();
		
		array_unshift($fields, 'v_id');
		$key = array_search('id', $fields);
		if ($key !== false) {
			unset($fields[$key]);
			array_unshift($fields, $table . '_id');
		} else {
			//only Type table with an id
			return false;
		}
		array_unshift($pk, 'v_id');
		$key = array_search('id', $pk);
		if ($key !== false) {
			unset($pk[$key]);
			array_unshift($pk, $table . '_id');
		}

		return static::getPdo()->createTable($ttable, $fields, array(), $pk, $execute);
	}
	
	public static function dropTableType() {
		return static::getPdo()->dropTable(static::getTableType());
	}
	
	public static function insertType($data) {
		if (!isset($data['id'])) {
			return false;
		}
		$uid = static::getTable() . '_id';
		$data[$uid] = $data['id'];
		unset($data['id']);
		$data['v_id'] = static::getPdo()->max(static::getTableType(), 'v_id', $uid . ' = ' . $data[$uid]);
		return static::getPdo()->insert(static::getTableType(), $data);
	}

	public function onPreSaveType() {
		if ($this->exists()) {
			$data = $this->_original;
			static::insertType($data);
		}
	}

}