<?php

namespace K;

/**
 * Implement softdelete system
 */
trait Behaviour_SoftDelete {
	/**
	 * Datetime when the record was deleted
	 * @var string
	 */
	protected $deleted_at;
	
	public function delete() {
		
	}
	
	public function restore() {
		
	}
}