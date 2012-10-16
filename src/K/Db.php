<?php

namespace K;

/**
 * Db manager
 * 
 * Handles multiple connections
 *
 * @author LeKoala
 */
class Db {
	/**
	 * Store all connections
	 * @var array
	 */
	protected static $connections = array();
	protected static $current = null;
	
	/**
	 * Configure databases
	 * @param array|object $config
	 * @throws Exception
	 */
	public static function configure($config) {
		if ($config instanceof Config) {
			$config = $config->get('Db', array());
		}
		if (is_array($config)) {
			foreach ($config as $k => $v) {
				if(!is_array($v)) {
					throw new Exception('Config values must be an array');
				}
				if(is_int($k)) {
					//only one connection
					self::$connections['default'] = new Pdo($v);
				}
				else {
					self::$connections[$k] = new Pdo($v);
				}
			}
		}
	}
	
	/**
	 * Get database
	 * @param string $name
	 * @return type
	 */
	public static function getDb($name = '') {
		if(self::$current !== $name) {
			if($name == '') {
				$name = 'default';
			}
			self::$current = $name;
		}
		if(isset(self::$connections[$name])) {
			return self::$connections[$name];
		}
	}
	
	
}
