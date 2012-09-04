<?php

namespace K\Data;

/**
 * Description of Abstract
 *
 * @author tportelange
 */
class Type_DateTime extends Type_Object {
	public function sqlType() {
		return 'DATETIME';
	}
}