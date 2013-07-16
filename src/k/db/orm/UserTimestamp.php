<?php

namespace k\db\orm;

/**
 * Timestamp
 * You must implement getCurrentUser on your orm
 */
trait Timestamp {

	public $created_at;
	public $created_by;
	public $updated_at;
	public $udpated_by;

	public static function typesTimestamp() {
		return [
			'created_at' => 'DATETIME',
			'updated_at' => 'DATETIME',
			'created_by' => 'INT',
			'updated_by' => 'INT'
		];
	}
	
	protected function onPreSaveTimestamp() {
		$user = static::getCurrentUser();
		if ($this->exists()) {
			$this->updated_at = date('Y-m-d H:i:s');
			if($user) {
				$this->updated_by = $user->id;
			}
		} else {
			$this->created_at = date('Y-m-d H:i:s');
			$this->updated_at = date('Y-m-d H:i:s');
			if($user) {
				$this->created_at = $user['id'];
				$this->updated_by = $user['id'];
			}
		}
	}

}