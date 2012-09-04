<?php

namespace K\Data;

/**
 * Description of Abstract
 *
 * @author tportelange
 */
abstract class Type_Abstract {

	protected $field;

	public function __construct($field) {
		$this->field = $field;
	}

	public function sqlType() {
		return 'VARCHAR';
	}
	
	public function forSql() {
		return $this->field . ' ' . $this->sqlType();
	}

	public function __toString() {
		return $this->forSql();
	}

}