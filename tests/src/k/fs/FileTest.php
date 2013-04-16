<?php

namespace k\fs;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2013-04-11 at 16:07:32.
 */
class FileTest extends \TestCase {

	const TEST_FILE_CONTENT = '<?php //some test file';
	
	/**
	 * @var File
	 */
	protected $o;
	/**
	 * @var File
	 */
	protected $i;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		$this->o = new File(DATA_DIR . '/sample/somefile.php');
		$this->i = new File(DATA_DIR . '/sample/Bondi Beach.jpg');
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown() {
		
	}

	/**
	 * @covers k\fs\File::createFromUpload
	 * @todo   Implement testCreateFromUpload().
	 */
	public function testCreateFromUpload() {
	}

	/**
	 * @covers k\fs\File::getMimeType
	 * @todo   Implement testGetMimeType().
	 */
	public function testGetMimeType() {
		$this->assertEquals('text/x-php',$this->o->getMimeType());
		$this->assertEquals('image/jpeg',$this->i->getMimeType());
	}

	/**
	 * @covers k\fs\File::getExtension
	 * @todo   Implement testGetExtension().
	 */
	public function testGetExtension() {
		$this->assertEquals('php',$this->o->getExtension());
		$this->assertEquals('jpg',$this->i->getExtension());
	}

	/**
	 * @covers k\fs\File::getMakeTime
	 * @todo   Implement testGetMakeTime().
	 */
	public function testGetMakeTime() {
		$this->assertInternalType('int', $this->o->getMakeTime());
		$this->assertInternalType('string', $this->o->getMakeTime('d/m/Y'));
	}

	/**
	 * @covers k\fs\File::getDirectory
	 * @todo   Implement testGetDirectory().
	 */
	public function testGetDirectory() {
		$this->assertInstanceOf('k\fs\Directory', $this->o->getDirectory());
	}

	/**
	 * @covers k\fs\File::rename
	 * @todo   Implement testRename().
	 */
	public function testRename() {
		$o = $this->o->rename('somefilenew.php');
		$this->assertTrue(is_file(DATA_DIR . '/sample/somefilenew.php'));
		$o->rename('somefile.php');
		$this->assertTrue(is_file(DATA_DIR . '/sample/somefile.php'));
	}

	/**
	 * @covers k\fs\File::move
	 * @todo   Implement testMove().
	 */
	public function testMove() {
		$o = $this->o->move(DATA_DIR . '/sample/subdir');
		$this->assertTrue(is_file(DATA_DIR . '/sample/subdir/somefile.php'));
		$o->move(DATA_DIR . '/sample');
		$this->assertTrue(is_file(DATA_DIR . '/sample/somefile.php'));
	}

	/**
	 * @covers k\fs\File::duplicate
	 * @todo   Implement testDuplicate().
	 */
	public function testDuplicate() {
		$o = $this->o->duplicate();
		$this->assertTrue(is_file(DATA_DIR . '/sample/somefile_1.php'));
		$o->remove();
	}

	/**
	 * @covers k\fs\File::write
	 * @todo   Implement testWrite().
	 */
	public function testWrite() {
		$c = $this->o->read();
		$this->o->write('some data');
		$this->assertEquals('some data',$this->o->read());
		$this->o->write($c);
		$this->assertEquals($c,$this->o->read());
	}

	/**
	 * @covers k\fs\File::read
	 * @todo   Implement testRead().
	 */
	public function testRead() {
		$this->assertEquals(self::TEST_FILE_CONTENT,$this->o->read());
	}

	/**
	 * @covers k\fs\File::remove
	 * @todo   Implement testRemove().
	 */
	public function testRemove() {
		$this->o->remove();
		$this->assertTrue(!is_file(DATA_DIR . '/sample/somefile.php'));
		file_put_contents(DATA_DIR . '/sample/somefile.php',self::TEST_FILE_CONTENT);
	}

	/**
	 * @covers k\fs\File::getSize
	 * @todo   Implement testGetSize().
	 */
	public function testGetSize() {
		$this->assertInternalType('int', $this->o->getSize());
		$this->assertInternalType('string', $this->o->getSize(true));
	}

	/**
	 * @covers k\fs\File::getMaxUploadSize
	 * @todo   Implement testGetMaxUploadSize().
	 */
	public function testGetMaxUploadSize() {
		$this->assertInternalType('int', File::getMaxUploadSize());
	}

	/**
	 * @covers k\fs\File::safeName
	 * @todo   Implement testSafeName().
	 */
	public function testSafeName() {
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
				'This test has not been implemented yet.'
		);
	}

}