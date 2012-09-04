<?php

namespace K\Data;

/**
 * Description of Abstract
 *
 * @author tportelange
 */
class Type_Geo extends Type_Abstract {

	public $lat;
	public $lng;

	public function sqlType() {
		return 'FLOAT(10,6)';
	}
	
	public function forSql() {
		return $this->field . '_lat ' . $this->sqlType() . ",\n" . $this->field . '_lng ' . $this->sqlType() ;
	}

}