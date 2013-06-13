<?php

namespace k\db\orm;

/**
 * Store Info info like name => value
 * Extra fields can be specified with the property $infoFields
 */
trait Info {

	public static function getTableInfo() {
		return static::getTable() . 'info';
	}

	public static function createTableInfo($execute = true) {
		$table = static::getTable();
		$ttable = static::getTableInfo();
		$fields = array(
			'id',
			$table . '_id',
			'name',
			'value'
		);
		//TODO: add extra fields
		return static::getPdo()->createTable($ttable, $fields, array(), null, $execute);
	}

	public static function createTable($execute = true, $foreignKeys = true) {
		$sql = parent::createTable($execute, $foreignKeys);
		$sql .= static::createTableInfo($execute);
		return $sql;
	}

	public static function dropTableInfo() {
		return static::getPdo()->dropTable(static::getTableInfo());
	}

	public function hasInfo($name = null, $value = null) {
		$table = static::getTable();
		$ttable = static::getTableInfo();
		$where = array($table . '_id' => $this->id);
		if ($name) {
			$where['name'] = $name;
		}
		if ($value) {
			$where['value'] = $value;
		}
		return static::getPdo()->count($ttable, $where);
	}

	public function addInfo($name, $value) {
		$table = static::getTable();
		$ttable = static::getTableInfo();
		if (is_array($value)) {
			foreach ($value as $v) {
				$this->addInfo($name, $v);
			}
		}
		if (!$this->hasInfo($name, $value)) {
			return static::getPdo()->insert($ttable, array($table . '_id' => $this->id, 'name' => $name, 'value' => $value));
		}
		return true;
	}

	public function removeInfo($name = null, $value = null) {
		$table = static::getTable();
		$ttable = static::getTableInfo();
		if (is_array($value)) {
			foreach ($value as $v) {
				$this->removeInfo($name, $v);
			}
		}
		$where = array($table . '_id' => $this->id);
		if ($name) {
			$where['name'] = $name;
		}
		if ($value) {
			$where['value'] = $value;
		}
		return static::getPdo()->delete($ttable, $where);
	}

	public function getInfo($name = null) {
		$table = static::getTable();
		$ttable = static::getTableInfo();
		$where = array($table . '_id' => $this->id, 'name' => $name);
		$results = static::getPdo()->select($ttable, $where);
		$Info = array();
		foreach ($results as $row) {
			if ($name) {
				$Info[] = $row['value'];
			} else {
				if (!isset($Info[$row['name']])) {
					$Info[$row['name']] = array();
				}
				$Info[$row['name']][] = $row['value'];
			}
		}
		return $Info;
	}

}