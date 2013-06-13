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
		$ttable = static::getTableLang();
		$fields = static::getFields();
		$pk = static::getPrimaryKeys();

		array_unshift($fields, 'v_id');
		$key = array_search('id', $fields);
		if ($key !== false) {
			unset($fields[$key]);
			array_unshift($fields, $table . '_id');
		} else {
			//only Lang table with an id
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
		$sql .= static::createTableLang($execute);
		return $sql;
	}

	public static function dropTableLang() {
		return static::getPdo()->dropTable(static::getTableLang());
	}

	public static function insertLang($data) {
		if (!isset($data['id'])) {
			return false;
		}
		$uid = static::getTable() . '_id';
		$data[$uid] = $data['id'];
		unset($data['id']);
		$data['v_id'] = static::getPdo()->max(static::getTableLang(), 'v_id', $uid . ' = ' . $data[$uid]);
		return static::getPdo()->insert(static::getTableLang(), $data);
	}

	public function onPreSaveLang() {
		if ($this->exists()) {
			$data = $this->_original;
			static::insertLang($data);
		}
	}

}