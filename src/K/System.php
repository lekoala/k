<?php

namespace K;

/**
 * Class to configure php settings.
 * 
 * Typically, this is mostly an helper to which you pass a config array.
 *
 * @author tportelange
 */
class System {
	
	/**
	 * Configure the class
	 * @param array|object $config
	 */
	public static function configure($config) {
		if ($config instanceof Config) {
			$config = $config->get('System', array());
		}
		if (is_array($config)) {
			foreach ($config as $k => $v) {
				$method = 'set' . ucfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $k))));
				//If we have a method like setThisProperty()
				if (method_exists(__CLASS__, $method)) {
					self::$method($v);
				//Otherwise call self::this_property();
				} else {
					self::$k($v);
				}
			}
		}
	}

	public static function getTimezone() {
		return date_default_timezone_get();
	}

	public static function setTimezone($value) {
		return date_default_timezone_set($value);
	}

	public static function getErrorReporting() {
		return error_reporting();
	}

	public static function setErrorReporting($value) {
		return error_reporting($value);
	}
	
	public static function getTimeLimit() {
		return ini_get('max_execution_time');
	}
	
	public static function setTimeLimit($value) {
		return set_time_limit($value);
	}

	public static function __callStatic($name, $arguments) {
		array_unshift($arguments, $name);
		call_user_func_array('ini_set', $arguments);
	}

}
