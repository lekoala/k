<?php

namespace K\Data;

/**
 * Description of Mapper
 *
 * @author tportelange
 */
class Mapper {
	public function create($class) {
		$fields = $class::getFields();
		
		
		echo '<pre>' . __LINE__ . "\n";
		print_r($fields);
		exit();
	}
}
