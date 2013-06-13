<?php

namespace k\db\orm;

/**
 * Log
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
		//TODO : add extra fields
		return static::getPdo()->createTable($ttable, $fields, array(), null, $execute);
	}
	
	public static function dropTableLog() {
		return static::getPdo()->dropTable(static::getTableLog());
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