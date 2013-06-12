<?php

namespace k\db\orm;

/**
 * Allows translation of a record
 * The record must specify a static property called langField with the fields to be translated 
 * Translated fields are added to the records and prefixed with the lang code
 */
trait Lang {

	public static function getTableLang() {
		return static::getTable() . 'lang';
	}

	public static function createTableLang($execute = true) {
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
	
	public static function createTable($execute = true, $foreignKeys = true) {
		$sql = parent::createTable($execute, $foreignKeys);
		$sql .= static::createTableVersion($execute);
		return $sql;
	}
	
	public static function dropTableVersion() {
		return static::getPdo()->dropTable(static::getTableVersion());
	}
	
	public static function dropTable() {
		static::dropTableVersion();
		return parent::dropTable();
	}

	public static function alterTable($execute = true) {
		$pdo = static::getPdo();
		$table = static::getTable();
		$ttable = static::getTableVersion();

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
		$sql = $pdo->alterTable($table, $addedFields, $removeFields, $execute);
		$sql .= $pdo->alterTable($ttable, $addedFields, $removeFields, $execute);
		return $sql;
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

	public function onPreSave() {
		$this->onPreSaveVersion();
	}

}