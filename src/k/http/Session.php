<?php

namespace k\http;

/**
 * Simple session wrapper
 */
class Session {

	protected $isActive;

	/**
	 * Construct a session wrapper and set defaults settings
	 */
	public function __construct() {
		ini_set('session.use_cookies', 1);
		register_shutdown_function('session_write_close');
	}

	/**
	 * Get is active internal flag
	 * @return bool
	 */
	public function getIsActive() {
		return $this->isActive;
	}

	/**
	 * Set is active internal flag
	 * @param bool $isActive
	 */
	public function setIsActive($isActive) {
		$this->isActive = $isActive;
	}

	/**
	 * Start the session
	 * @return boolean
	 */
	public function start() {
		if (!$this->isActive()) {
			$this->isActive = true;
			return session_start();
		}
		return true;
	}

	/**
	 * Close early the session and remove locking on the session file
	 * Closing early helps with concurrent requests from a same user (eg : 
	 * multiple file uploads)
	 */
	public function close() {
		session_write_close();
		$this->isActive = false;
	}

	/**
	 * Get a session value or the whole session array. / notation allowed
	 * 
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function get($key = null, $default = null) {
		//if the $_SESSION is not set, no session was started
		if (!isset($_SESSION)) {
			$this->start();
		}
		if ($key === null) {
			return $_SESSION;
		}
		$loc = &$_SESSION;
		foreach (explode('/', $key) as $step) {
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
	public function take($key, $default = null) {
		$val = $this->get($key, $default);
		$this->delete($key);
		return $val;
	}

	/**
	 * Set a session value and make sure it's started
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return mixed 
	 */
	public function set($key, $value) {
		//To write something, we need to make sure it's active
		if (!$this->isActive()) {
			$this->start();
		}
		$loc = &$_SESSION;
		foreach (explode('/', $key) as $step) {
			$loc = &$loc[$step];
		}
		$loc = $value;
		return $_SESSION;
	}

	/**
	 * A value to a session array
	 * 
	 * @param string $key
	 * @param string $value
	 * @return string
	 */
	public function add($key, $value) {
		$v = $this->get($key);
		if (!is_array($v)) {
			$v = array();
		}
		$v[] = $value;
		return $this->set($key, $v);
	}

	/**
	 * Delete an element from the session
	 * 
	 * @param string $key
	 * @return boolean
	 */
	public function delete($key) {
		$loc = &$_SESSION;
		$parts = explode('/', $key);
		while (count($parts) > 1) {
			$step = array_shift($parts);
			if (!isset($loc[$step])) {
				return false;
			}
			$loc = &$loc[$step];
		}
		unset($loc[array_shift($parts)]);
		return $_SESSION;
	}

	/**
	 * Destroys the session completely
	 * 
	 * @return bool
	 */
	public function destroy() {
		session_unset();
		$_SESSION = array();
		setcookie(session_name(), null, 0, "/");
		return session_destroy();
	}

	/**
	 * Tell if there is a session active
	 * 
	 * @link http://stackoverflow.com/questions/3788369/how-to-tell-if-a-session-is-active
	 * @param bool $force Force check against actual php instead of internal flag
	 * @return bool 
	 */
	public function isActive($force = false) {
		//check with internal flag
		if (!$force && $this->isActive !== null) {
			return $this->isActive;
		}
		//use php 5.4 
		if (function_exists('session_status')) {
			return session_status() === PHP_SESSION_ACTIVE;
		}
		//fallback with a rather hacky way
		$setting = 'session.use_trans_sid';
		$current = ini_get($setting);
		if (false === $current) {
			throw new Exception('Unable to determine if the session is opened by using setting ' . $setting);
		}
		$result = @ini_set($setting, $current);
		return $result !== $current;
	}

}