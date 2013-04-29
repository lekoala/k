<?php

namespace k\cache;

use \InvalidArgumentException;

/**
 * Normalize the use of cache classes
 *
 * @author tportelange
 */
abstract class CacheAbstract implements CacheInterface {

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

	public function set($key, $value, $ttl = 0) {
		$this->validateKey($key);
		$value = serialize($value);
		return $this->_set($key, $value, $ttl);
	}
	
	public function clear($key = null) {
		if ($key) {
			$this->validateKey($key);
		}
		return $this->_clear($key);
	}
	
	/**
	 * @param type $key
	 * @return type
	 * @throws InvalidArgumentException
	 */
	protected function validateKey($key) {
		if (empty($key) || preg_match('/[^a-z0-9.#$-_]/i', $key)) {
			throw new InvalidArgumentException($key);
		}
	}

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

	abstract protected function _get($key);

	abstract protected function _set($key, $value, $ttl = 0);
}