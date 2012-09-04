<?php

namespace K\Data;

/**
 * Description of Connection
 *
 * @author tportelange
 */
class Connection {

	protected static $connections = array();

	public static function create($dsn, $name = 'default', $options = array()) {
		if (is_string($dsn)) {
			$dsn = self::parseDsn($dsn);
		}
		$driverClass = '\K\Data\Driver_' . ucfirst($dsn['driver']);
		if(!class_exists($driverClass)) {
			throw new Exception('Driver ' . $driverClass . ' does not exists');
		}
		
		self::$connections[$name] = new $driverClass($dsn, $options);
	}

	public static function get($name = 'default') {
		if(isset(self::$connections[$name])) {
			return self::$connections[$name];
		}
	}

	protected static function parseDsn($dsn) {
		if(strpos($dsn, 'sqlite') === 0) {
			$driver = 'sqlite';
			$database = substr($dsn, strpos($dsn, ':') + 1, strlen($dsn));
			return compact('driver','database');
		}
		
		preg_match('%^([^/]+)?://?(?:([^/@]*?)(?::([^/@:]+?)@)?([^/@:]+?)(?::([^/@:]+?))?)?/(.+)$%', $dsn, $matches);
		return array(
			'driver' => isset($matches[1]) ? $matches[1] : null,
			'username' => isset($matches[2]) ? $matches[2] : null,
			'password' => isset($matches[3]) ? $matches[3] : null,
			'host' => isset($matches[4]) ? $matches[4] : null,
			'port' => isset($matches[5]) ? $matches[5] : null,
			'database' => isset($matches[6]) ? $matches[6] : null
		);
	}

}