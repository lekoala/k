<?php

namespace K\Data;

/**
 * Description of Abstract
 *
 * @author tportelange
 */
class Type_String extends Type_Abstract {
	public function sqlType() {
		return 'VARCHAR(255)';
	}
}