<?php

namespace K\Data;

/**
 * Description of Abstract
 *
 * @author tportelange
 */
class Type_Boolean extends Type_Abstract {
	public function sqlType() {
		return 'TINYINT(1)';
	}
}