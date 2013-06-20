<?php

namespace k\cache;

/**
 * Null cache
 *
 * @author lekoala
 */
class NullCache extends CacheAbstract {
	
	public function clean() {
		return true;
	}

	protected function _clear($key = null) {
		return true;
	}

	protected function _get($key) {
		return null;
	}

	protected function _set($key, $value, $ttl = 0) {
		return true;
	}
}