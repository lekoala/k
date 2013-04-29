<?php

namespace k\cache;

/**
 * Cache interface that can set or get values
 *
 * @author lekoala
 */
interface CacheInterface {
	public function get($key);
	public function set($key,$value,$ttl);
	public function clear($key = null);
	public function clean();
}