<?php

namespace K;

class Session {

	/**
	 * Get a session value or the whole session array. Dot notation allowed
	 * 
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	static function get($key = null, $default=null) {
		//Not started yet!
		if(!isset($_SESSION)) {
			session_start();
		}
		if($key === null) {
			return $_SESSION;
		}
		$loc = &$_SESSION;
		foreach (explode('.', $key) as $step) {
			if (isset($loc[$step])) {
				$loc = &$loc[$step];
			} else {
				return $default;
			}
		}
		return $loc;
	}
	
	/**
	 * Get and erase a value from session
	 * 
	 * @param string $key
	 * @param mixed $default
	 */
	static function take($key, $default = null) {
		$val = self::get($key, $default);
		self::delete($key);
		return $val;
	}

	/**
	 * Set a session value and make sure it's started
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return mixed 
	 */
	static function set($key, $value) {
		//To write something, we need to make sure it's active
		if (!self::isActive()) {
			session_regenerate_id(); //because it's never too safe
			session_start();
		}
		$loc = &$_SESSION;
		foreach (explode('.', $key) as $step) {
			$loc = &$loc[$step];
		}
		$loc = $value;
		return $_SESSION;
	}
	
	/**
	 * Delete an element from the session
	 * 
	 * @param string $key
	 * @return boolean
	 */
	static function delete($key) {
		$loc = &$_SESSION;
		$parts = explode('.', $key);
		while(count($parts) > 1) {
			$step = array_shift($parts);
			if(!isset($loc[$step])) {
				return false;
			}
			$loc = &$loc[$step];
		}
		unset($loc[array_shift($parts)]);
		return $_SESSION;
	}

	/**
	 * Tell if there is a session active
	 * 
	 * @link http://stackoverflow.com/questions/3788369/how-to-tell-if-a-session-is-active
	 * @return bool 
	 */
	static function isActive() {
		if(function_exists('session_status')) {
			return session_status() === PHP_SESSION_ACTIVE;
		}
		$setting = 'session.use_trans_sid';
		$current = ini_get($setting);
		if (false === $current) {
			throw new Exception('Unable to determine if the session is opened by using setting ' . $setting);
		}
		$result = @ini_set($setting, $current);
		return $result !== $current;
	}

}