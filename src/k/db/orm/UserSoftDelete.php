<?php

namespace k\db\orm;

/**
 * Implement softdelete system
 */
trait SoftDelete {

	public $deleted_at;
	public $deleted_by;
	
	public static function queryActive() {
		$q = parent::query();
		$q->whereNull(static::getTableName() . '.deleted_at');
		return $q;
	}

	public function onPreRemove() {
		$user = static::getCurrentUser();
		if ($this->exists()) {
			$this->deleted_at = date('Y-m-d H:i:s');
			if($user) {
				$this->deleted_by = $user['id'];
			}
			
			$this->save();
		}
		return false; //cancel the traditional remove
	}

	public function restore() {
		if ($this->deleted_at) {
			$this->deleted_at = null;
			$this->deleted_by = null;
			return $this->save();
		}
		return false;
	}

}