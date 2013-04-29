<?php

/**
 * Test case that provide the required architecture to run tests properly
 *
 * @author lekoala
 */
class TestCase extends \PHPUnit_Framework_TestCase{
	
	protected $connections = array();
	
	protected function buildDataDir() {
		
	}
	
	protected function getDataDir() {
		return DATA_DIR;
	}
	
	protected function getDb($name = null) {
		$pdo = new \PDO('sqlite::memory:');
	}
}