<?php

namespace k\db\orm;

/**
 * Timestamp
 */
trait Timestamp {

	public $created_at;
	public $updated_at;

	public static function typesTimestamp() {
		return [
			'created_at' => 'DATETIME',
			'updated_at' => 'DATETIME'
		];
	}

	protected function onPreSaveTimestamp() {
		if ($this->exists()) {
			$this->updated_at = date('Y-m-d H:i:s');
		} else {
			$this->created_at = date('Y-m-d H:i:s');
			$this->updated_at = date('Y-m-d H:i:s');
		}
	}

}