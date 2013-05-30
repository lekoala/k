<?php

namespace k\sql\orm;

/**
 * Timestamp
 */
trait Timestamp {

	public static $permissionsFields = array(
		'created_at' => 'DATETIME',
		'updated_at' => 'DATETIME'
	);

	public function onPreSaveTimestamp() {
		if ($this->exists()) {
			$this->updated_at = date('Y-m-d H:i:s');
		} else {
			$this->created_at = date('Y-m-d H:i:s');
			$this->updated_at = date('Y-m-d H:i:s');
		}
	}

	public static function onInsert(&$data) {
		$data['created_at'] = date('Y-m-d H:i:s');
		$data['updated_at'] = date('Y-m-d H:i:s');
	}

	public static function onUpdate(&$data) {
		$data['updated_at'] = date('Y-m-d H:i:s');
	}

	public function onPreSave() {
		$this->onPreSaveTimestamp();
	}

}