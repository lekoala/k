<?php

namespace k\db\orm;

/**
 * Version
 */
trait Version {

	public static function getTableVersion() {
		return static::getTable() . 'version';
	}

	public static function createTableVersion($execute = true) {
		$table = static::getTable();
		$ttable = static::getTableVersion();
		$fields = static::getFields();
		$pk = static::getPrimaryKeys();
		
		array_unshift($fields, 'v_id');
		$key = array_search('id', $fields);
		if ($key !== false) {
			unset($fields[$key]);
			array_unshift($fields, $table . '_id');
		} else {
			//only version table with an id
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
		
	public static function dropTableVersion() {
		return static::getPdo()->dropTable(static::getTableVersion());
	}
	
	public static function insertVersion($data) {
		if (!isset($data['id'])) {
			return false;
		}
		$uid = static::getTable() . '_id';
		$data[$uid] = $data['id'];
		unset($data['id']);
		$data['v_id'] = static::getPdo()->max(static::getTableVersion(), 'v_id', $uid . ' = ' . $data[$uid]);
		return static::getPdo()->insert(static::getTableVersion(), $data);
	}

	public function onPreSaveVersion() {
		if ($this->exists()) {
			$data = $this->_original;
			static::insertVersion($data);
		}
	}

}