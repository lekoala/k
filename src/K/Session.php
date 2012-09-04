<?php

namespace k;

class session {

	/**
	 * Get a session value
	 * 
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	static function get($key, $default=null) {
		return arrayutils::get($_SESSION, $key, $default);
	}

	/**
	 * Set a session value
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return mixed 
	 */
	static function set($key, $value) {
		if (!self::is_active()) {
			session_start();
		}

		$_SESSION[$name] = $value;
		return $value;
	}

	/**
	 * Tell if there is a session active
	 * 
	 * @link http://stackoverflow.com/questions/3788369/how-to-tell-if-a-session-is-active
	 * @return bool 
	 */
	static function is_active() {
		$setting = 'session.use_trans_sid';
		$current = ini_get($setting);
		if (false === $current) {
			throw new Exception('Unable to determine if the session is opened by using setting ' . $setting);
		}
		$result = @ini_set($setting, $current);
		return $result !== $current;
	}

}