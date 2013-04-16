<?php

namespace K\Orm;

/**
 * Meta - store meta info like name => value
 */
trait Meta {

	public static function getTableMeta() {
		return static::getTable() . 'meta';
	}

	public static function createTableMeta($execute = true) {
		$table = static::getTable();
		$ttable = static::getTableMeta();
		$fields = array(
			'id',
			$table . '_id',
			'name',
			'value'
		);
		return static::getPdo()->createTable($ttable, $fields, array(), null, $execute);
	}

	public static function createTable($execute = true, $foreignKeys = true) {
		$sql = parent::createTable($execute, $foreignKeys);
		$sql .= static::createTableMeta($execute);
		return $sql;
	}

	public static function dropTableMeta() {
		return static::getPdo()->dropTable(static::getTableMeta());
	}

	public static function dropTable() {
		static::dropTableMeta();
		return parent::dropTable();
	}

	public function hasMeta($name = null, $value = null) {
		$table = static::getTable();
		$ttable = static::getTableMeta();
		$where = array($table . '_id' => $this->id);
		if ($name) {
			$where['name'] = $name;
		}
		if ($value) {
			$where['value'] = $value;
		}
		return static::getPdo()->count($ttable, $where);
	}

	public function addMeta($name, $value) {
		$table = static::getTable();
		$ttable = static::getTableMeta();
		if (is_array($value)) {
			foreach ($value as $v) {
				$this->addMeta($name, $v);
			}
		}
		if (!$this->hasMeta($name, $value)) {
			return static::getPdo()->insert($ttable, array($table . '_id' => $this->id, 'name' => $name, 'value' => $value));
		}
		return true;
	}

	public function removeMeta($name = null, $value = null) {
		$table = static::getTable();
		$ttable = static::getTableMeta();
		if (is_array($value)) {
			foreach ($value as $v) {
				$this->removeMeta($name, $v);
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

	public function getMeta($name = null) {
		$table = static::getTable();
		$ttable = static::getTableMeta();
		$where = array($table . '_id' => $this->id, 'name' => $name);
		$results = static::getPdo()->select($ttable, $where);
		$meta = array();
		foreach ($results as $row) {
			if ($name) {
				$meta[] = $row['value'];
			} else {
				if (!isset($meta[$row['name']])) {
					$meta[$row['name']] = array();
				}
				$meta[$row['name']][] = $row['value'];
			}
		}
		return $meta;
	}

}