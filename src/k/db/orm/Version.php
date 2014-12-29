<?php

namespace k\db\orm;

/**
 * Version
 */
trait Version {

	public static function getTableVersion() {
		return static::getTableName() . 'version';
	}

	public static function createTableVersion($execute = true) {
		$fields = static::getFields();
		if (!in_array('id', $fields)) {
			throw new \Exception('To use this trait, the table must have a field id');
		}
		$table = static::getTable();
		$ttable = static::getTableVersion();
		$fields = static::getFields();
		$pk = static::getPrimaryKeys();

		array_unshift($fields, 'v_id');
		$key = array_search('id', $fields);
		if ($key !== false) {
			unset($fields[$key]);
			array_unshift($fields, static::getForForeignKey());
		}
		array_unshift($pk, 'v_id');
		$key = array_search('id', $pk);
		if ($key !== false) {
			unset($pk[$key]);
			array_unshift($pk, static::getForForeignKey());
		}
		$fk = [];
		return static::getPdo()->createTable($ttable, $fields, $pk, $fk, $execute);
	}

	public static function dropTableVersion() {
		return static::getPdo()->dropTable(static::getTableVersion());
	}

	public static function insertVersion($data) {
		if (!isset($data['id'])) {
			return false;
		}
		$fk = static::getForForeignKey();
		$data[$fk] = $data['id'];
		unset($data['id']);
		$data['v_id'] = static::getPdo()->max(static::getTableVersion(), 'v_id', $fk . ' = ' . $data[$fk]);
		$data['v_id']++;
		return static::getPdo()->insert(static::getTableVersion(), $data);
	}

	public function onPreSaveVersion() {
		if ($this->exists()) {
			$data = $this->_original;
			static::insertVersion($data);
		}
	}

}