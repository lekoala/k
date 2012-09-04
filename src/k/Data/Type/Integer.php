<?php

namespace K\Data;

/**
 * Description of Abstract
 *
 * @author tportelange
 */
class Type_Integer extends Type_Abstract {
	public function sqlType() {
		return 'INTEGER';
	}
}