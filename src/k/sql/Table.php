<?php

namespace k\sql;

/**
 * Table
 *
 * @author lekoala
 */
class Table {
	
	protected $fields = array();
	protected $hasOne = array();
	protected $hasMany = array();
	protected $manyMany = array();

	public function __construct() {
		
	}

}