<?php

namespace K;

/**
 * Cookie wrapper class
 */
class Cookie {

	/**
	 * Get a cookie
	 * 
	 * @param string $key
	 * @param string $default
	 * @return string 
	 */
	static function get($key, $default = null) {
		if (isset($_COOKIE[$key])) {
			return $_COOKIE[$key];
		}
		return $default;
	}

	/**
	 * Set a cookie
	 * 
	 * @param string $key
	 * @param string $value
	 * @param int|string (optional) $expire Can be a timestamp or a string (e : +1 week). 0 means when browser closes
	 * @param string $path (optional) '/' for the whole domain or '/foo/' for foo directory 
	 * @param string $domain (optional) .domain.tld or www.domain.tld
	 * @param bool $secure (optional)
	 * @param bool $httponly (optional)
	 * @return mixed
	 */
	static function set($key, $value = null, $expire = 0, $path = null, $domain = null, $secure = false, $httponly = true) {
		$ob = ini_get('output_buffering');

		// Abort the method if headers have already been sent, except when output buffering has been enabled 
		if (headers_sent() && (bool) $ob === false || strtolower($ob) == 'off') {
			return false;
		}

		// Allow time to be set as string (like +1 week)
		if ($expire && !is_numeric($expire)) {
			$expire = strtotime($expire);
		}

		// Make sure domain is set correctly
		if (!empty($domain)) {
			// Fix the domain to accept domains with and without 'www.'. 
			if (strtolower(substr($domain, 0, 4)) == 'www.') {
				$domain = substr($domain, 4);
			}

			// Add the dot prefix to ensure compatibility with subdomains 
			if (substr($domain, 0, 1) != '.') {
				$domain = '.' . $domain;
			}

			// Remove port information. 
			$port = strpos($domain, ':');

			if ($port !== false) {
				$domain = substr($domain, 0, $port);
			}
		}

		// rfc 2109 compatible cookie set
		header('Set-Cookie: ' . rawurlencode($key) . '=' . rawurlencode($value)
				. (empty($domain) ? '' : '; Domain=' . $domain)
				. (empty($expire) ? '' : '; Max-Age=' . $expire)
				. (empty($path) ? '' : '; Path=' . $path)
				. (!$secure ? '' : '; Secure')
				. (!$httponly ? '' : '; HttpOnly'), false);
		return $value;
	}
	
	/**
	 * Delete a cookie
	 * @param string $name
	 * @return bool
	 */
	static function delete($name) {
		unset($_COOKIE[$name]);
		return setcookie($name, NULL, -1);	
	}
}