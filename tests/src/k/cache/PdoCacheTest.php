<?php

namespace k\cache;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2013-04-11 at 12:42:29.
 */
class PdoCacheTest extends \TestCase {

	/**
	 * @var PdoCache
	 */
	protected $object;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		$pdo = new \PDO('sqlite::memory:');
		
		$pdo->query('CREATE TABLE cache (
key TEXT,
value TEXT,
expire_ts INTEGER
);');
		$this->object = new PdoCache($pdo);
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown() {
		
	}

	public function testGetSet()
    {
         $o = $this->object;
		 $o->set('key', 'value');
		 $this->assertEquals('value',$o->get('key'));
		 
		 //ttl
		 $o->set('key_ttl', 'value','1 second');
		 $this->assertEquals('value',$o->get('key_ttl'));
		 sleep(2);
		 $this->assertEquals(null,$o->get('key_ttl'));
		 
		 //default
		 $this->assertEquals('default',$o->get('no_value','default'));
		 $def = function() { return 'default'; };
		 $this->assertEquals('default',$o->get('no_value',$def));
		 
		 //unsafe key
		 $k = 'Very unsafe key      /ˈrʊʃə/';
		 $this->setExpectedException('InvalidArgumentException');
		 $o->set($k, 'my value');
		 $this->assertEquals('my value',$o->get($k));
    }
	
	public function testClear() {
		 $o = $this->object;
		 $o->set('key', 'value');
		 $this->assertEquals('value',$o->get('key'));
		 $this->assertTrue($o->clear('key'));
		 $this->assertEquals(null,$o->get('key'));
		 
		 $o->set('v1','v1');
		 $o->set('v2','v2');
		 $o->clear();
		 $this->assertEquals(null,$o->get('v1'));
		 
		 $res = $o->getPdo()->query('SELECT * FROM cache');
		 $this->assertEquals(array(),$res->fetchAll());
	}
	
	public function testClean() {
		 $o = $this->object;
		 $o->clear();
		 $o->set('key', 'value',1);
		 sleep(2);
		 $o->clean();
		 
		 $res = $o->getPdo()->query('SELECT * FROM cache');
		 $this->assertEquals(array(),$res->fetchAll());
	}
}
