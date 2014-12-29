<?php

namespace k\http;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2013-05-02 at 14:35:42.
 */
class SessionTest extends \TestCase {

	/**
	 * @var Session
	 */
	protected $object;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		$this->object = new Session;
		$this->object->setIsActive(true);
		
		//we can close the session only once in this test and it can't be reopened'
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown() {
		
	}

	/**
	 * @covers k\http\Session::start
	 */
	public function testStart() {
		$this->assertNotEquals("", session_id());
	}

	/**
	 * @covers k\http\Session::close
	 */
	public function testClose() {
	}

	/**
	 * @covers k\http\Session::get
	 */
	public function testGet() {
		$_SESSION['key'] = 'test';
		$_SESSION['arr'] = array('val' => 'test');

		$this->assertEquals('test', $this->object->get('key'));
		$this->assertEquals('test', $this->object->get('arr/val'));
	}

	/**
	 * @covers k\http\Session::take
	 */
	public function testTake() {
		$_SESSION = array();
		$_SESSION['key'] = 'test';

//		$this->assertEmpty($_SESSION, $this->object->take('key'));
	}

	/**
	 * @covers k\http\Session::set
	 */
	public function testSet() {
		$this->object->set('key', 'value');
		
		$this->assertEquals('value',$this->object->get('key'));
	}

	/**
	 * @covers k\http\Session::add
	 */
	public function testAdd() {
		$_SESSION = array(
			'arr' => array()
		);
		
		$this->object->addElement('arr','key');
		$this->assertEquals(array('key'),$_SESSION['arr']);
		$this->object->addElement('arr','key2');
		$this->assertEquals(array('key','key2'),$_SESSION['arr']);
	}

	/**
	 * @covers k\http\Session::delete
	 */
	public function testDelete() {
		$_SESSION = array(
			'arr' => array()
		);
		
		$this->object->delete('arr');
		$this->assertEmpty($_SESSION);
	}

	/**
	 * @covers k\http\Session::destroy
	 */
	public function testDestroy() {
		$_SESSION = array(
			'arr' => array()
		);
		
//		$this->object->destroy();
//		$this->assertEmpty($_SESSION);
	}

	/**
	 * @covers k\http\Session::isActive
	 */
	public function testIsActive() {
		$this->assertTrue($this->object->isActive());
		$this->assertTrue($this->object->isActive(true));
		$this->object->close();
		$this->assertFalse($this->object->isActive());
		$this->assertFalse($this->object->isActive(true));
	}

}
