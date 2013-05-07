<?php

namespace k\sql\orm;

/**
 * Implement softdelete system
 */
trait SoftDelete {
	/**
	 * Datetime when the record was deleted
	 * @var string
	 */
	protected $deleted_at;
	
	public static function get() {
		$q = parent::get();
		$q->whereNull(static::getTable() . '.deleted_at');
		return $q;
	}
	
	public function onPreRemove() {
		if($this->exists()) {
			$this->deleted_at = date('Y-m-d H:i:s');
			$this->save();
		}
		return false; //cancel the traditional remove
	}
		
	public function restore() {
		if($this->deleted_at) {
			$this->deleted_at = null;
			return $this->save();
		}
		return false;
	}
}