<?php

namespace k;

/**
 * Simple multi modal cache provider
 */
class cache {
	const APC = 'apc';
	const REDIS = 'redis';
	const XCACHE = 'xcache';

	protected static $cache;

	/**
	 * Set the cache backend
	 * 
	 * @param type $backend 
	 */
	public function init($backend) {
		self::$cache = $backend;
	}

	/**
	 * Get a value from cache
	 * 
	 * @param string $key
	 * @return mixed
	 */
	public function get($key) {
		$cache = self::$cache;

		switch ($cache) {
			case 'apc':
				$value = apc_fetch($key);
				break;

			case $cache instanceof PDO:
				$stmt = $cache->prepare("SELECT value FROM cache WHERE key = :key AND (expire = 0 OR expire >= :time)");
				$time = time();
				$stmt->execute(compact('key', 'time'));
				$res = $stmt->fetch();
				if ($res) {
					$value = $res['value'];
				}
				break;

			case $cache instanceof Memcache:
				$value = $cache->get($key);
				break;

			case 'redis':
				$value = $cache->get($key);
				break;

			case 'xcache':
				$value = xcache_get($key);
				break;

			default :
				if (is_file($cache . DIRECTORY_SEPARATOR . $key)) {
					$cached_content = file_get_contents($cache . DIRECTORY_SEPARATOR . $key);
					$cached_content = unserialize($cached_content);
					if ($cached_content['expire'] == 0 || $cached_content['expire'] >= time()) {
						$value = $cached_content['value'];
					}
				}
				break;
		}
		return $value;
	}

	/**
	 * Set value in cache
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @param int $ttl
	 * @return bool 
	 */
	public function set($key, $value, $ttl = null) {
		$expire = (!$ttl) ? 0 : time() + $ttl;
		switch ($case) {
			case 'apc':
				return apc_store($key, $value, $ttl);

			case $cache instanceof PDO:
				$stmt = $cache->prepare("SELECT value FROM cache WHERE key = :key");
				$stmt->execute(compact('key'));
				$res = $stmt->fetch();
				if ($res) {
					$stmt = $cache->prepare("INSERT INTO cache(key, value, expire) VALUES (:key,:value,:expire)");
					return $stmt->execute(compact('key', 'value', 'expire'));
				} else {
					$stmt = $cache->prepare("UPDATE cache SET key = :key, value = :value, expire = :expire)");
					return $stmt->execute(compact('key', 'value', 'expire'));
				}

			case $cache instanceof Memcache:
				if ($ttl > 2592000) {
					$ttl = time() + 2592000;
				}
				$result = $cache->replace($key, $value, 0, $ttl);
				if (!$result) {
					return $cache->set($key, $value, 0, $ttl);
				}
				return $result;

			case 'redis':
				if ($ttl) {
					return $cache->setex($key, $value, $ttl);
				}
				return $cache->set($key, $value);

			case 'xcache':
				return xcache_set($key, $value, $ttl);

			default :
				if (!is_dir($cache)) {
					throw new Exception($cache . ' is not a valid directory');
				}

				$cached_content = array(
					'expire' => $expire,
					'value' => $value
				);
				$cached_content = serialize($cached_content);
				return file_put_contents($cache . DIRECTORY_SEPARATOR . $key, $cached_content);
		}
	}

}