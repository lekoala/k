<?php

namespace k\db\orm;

/**
 * Meta
 */
trait Log {
	
	public static function getTableLog() {
		return static::getTable() . 'log';
	}
	
	public static function createTableLog($execute = true) {
		$table = static::getTable();
		$ttable = static::getTableLog();
		$fields = array(
			'id',
			$table . '_id',
			'message',
			'created_at'
		);
		return static::getPdo()->createTable($ttable, $fields, array(), null, $execute);
	}
	
	public static function createTable($execute = true, $foreignKeys = true) {
		$sql = parent::createTable($execute, $foreignKeys);
		$sql .= static::createTableLog($execute);
		return $sql;
	}
	
	public static function dropTableLog() {
		return static::getPdo()->dropTable(static::getTableLog());
	}
	
	public static function dropTable() {
		static::dropTableLog();
		return parent::dropTable();
	}
	
	public function log($message) {
		$table = static::getTable();
		return static::getPdo()->insert(static::getTableLog(), array(
			$table . '_id' => $this->id,
			'message' => $message,
			'created_at' => date('Y-m-d H:i:s')
		));
	}
	
	public function getLog($date = null, $message = null) {
		$table = static::getTable();
		$where = array($table . '_id' => $this->id);
		if($date) {
			$where['datetime'] = $date;
		}
		if($message) {
			$where['message'] = '%' . $message . '%';
		}
		return static::getPdo()->select(static::getTableLog());
	}
}