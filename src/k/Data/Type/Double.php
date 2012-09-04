<?php

namespace K\Data;

/**
 * Description of Abstract
 *
 * @author tportelange
 */
class Type_Double extends Type_Abstract {
	public function sqlType() {
		return 'FLOAT';
	}
}