<?php

namespace k\cache;

use \InvalidArgumentException;

/**
 * Normalize the use of cache classes
 *
 * @author tportelange
 */
abstract class CacheAbstract implements CacheInterface {

	/**
	 * Get a value from cache
	 * 
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function get($key, $default = null) {
		$this->validateKey($key);
		$result = $this->_get($key);
		if ($result === null) {
			if (is_callable($default)) {
				return $default();
			}
			return $default;
		}
		return unserialize($result);
	}

	/**
	 * Set a value in the cache
	 * The value is serialized
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @param int|string $ttl A number of seconds or a strtotime expression like +1 day
	 * @return bool
	 */
	public function set($key, $value, $ttl = 0) {
		$this->validateKey($key);
		$value = serialize($value);
		return $this->_set($key, $value, $ttl);
	}

	/**
	 * Clear the cache
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function clear($key = null) {
		if ($key) {
			$this->validateKey($key);
		}
		return $this->_clear($key);
	}

	/**
	 * Validate key
	 * 
	 * @param type $key
	 * @return type
	 * @throws InvalidArgumentException
	 */
	protected function validateKey($key) {
		if (empty($key) || preg_match('/[^a-z0-9.#$-_]/i', $key)) {
			throw new InvalidArgumentException("'$key' is not a valid key");
		}
	}

	/**
	 * Convert ttl argument
	 * 
	 * @param string|int $ttl
	 * @return int
	 */
	protected function getExpire($ttl = 0) {
		$expire = 0;
		if (is_string($ttl)) {
			$expire = strtotime($ttl);
		} elseif ($ttl) {
			$expire = time() + $ttl;
		}
		return $expire;
	}

	abstract protected function _clear($key = null);

	/**
	 * Get must return null if no value
	 */
	abstract protected function _get($key);

	/**
	 * Use getExpire method to convert ttl if needed
	 */
	abstract protected function _set($key, $value, $ttl = 0);
}