<?php

namespace k\db\orm;

/**
 * Timestamp
 */
trait Timestamp {

	public static $fieldsTimestamp = array(
		'created_at' => 'DATETIME',
		'updated_at' => 'DATETIME'
	);

	public function onPreSaveTimestamp() {
		if ($this->exists()) {
			$this->saveData['updated_at'] = date('Y-m-d H:i:s');
		} else {
			$this->saveData['created_at'] = date('Y-m-d H:i:s');
			$this->saveData['updated_at'] = date('Y-m-d H:i:s');
		}
	}

}