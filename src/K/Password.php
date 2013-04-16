<?php

namespace K;

/**
 * Static interface to PHP password api
 * Requires php 5.5 or password_compat lib
 * Please store hash in a VARCHAR(255) column
 * 
 * @link https://github.com/ircmaxell/password_compat/blob/master/lib/password.php
 */
class Password {
	
	use TConfigure;

	protected static $cost = 10;
	protected static $algorithm = 1;

	/**
	 * The function which creates new password hashes.
	 * @param string $password
	 * @param int $algorithm
	 * @param array $options
	 * @return string
	 */
	public static function hash($password, $algorithm = null, $options = null) {
		if (!$algorithm) {
			$algorithm = self::$algorithm;
		}
		if (!$options) {
			$options = array('cost' => self::$cost);
		}
		return \password_hash($password, $algorithm, $options);
	}

	/**
	 * This function gets the information used to generate a hash. The returned array has two keys, algo and options.
	 * @param string $hash
	 * @return array
	 */
	public static function getInfo($hash) {
		return \password_get_info($hash);
	}

	/**
	 * This function checks to see if the supplied hash implements the algorithm and options provided. If not, it is assumed that the hash needs to be rehashed.
	 * @param string $hash
	 * @param int $algorithm
	 * @param array $options
	 * @return bool
	 */
	public static function needsRehash($hash, $algorithm, $options) {
		return \password_needs_rehash($hash, $algorithm, $options);
	}

	/**
	 * The function which verifies an existing hash. This hash can be created via password_hash(), or a normal crypt() hash. The only thing it provides on top of crypt() is resistance to timing attacks by using a constant-time comparison function.
	 * @param string $password
	 * @param string $hash
	 * @return bool
	 */
	public static function verify($password, $hash) {
		return \password_verify($password, $hash);
	}

}
