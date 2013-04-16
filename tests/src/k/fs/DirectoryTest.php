<?php

namespace k\fs;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2013-04-15 at 17:28:10.
 */
class DirectoryTest extends \TestCase {

	/**
	 * @var Directory
	 */
	protected $o;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		$this->o = new Directory(DATA_DIR . '/sample');
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown() {
		
	}
	
	protected function testSampleDir() {
		foreach($this->o as $fi) {
			if($fi->getBasename() == 'somefile.php') {
				$this->assertInstanceOf($expected, $fi);
			}
		}
	}

}
