<?php

namespace K;

class password {

	/**
	 * Generate a strong password with at least a lower case letter, an uppercase letter,
	 * one digit and one special character.
	 * 
	 * The generated password does not contain any ambigous character such as i, l, 1, o, 0.
	 * 
	 * @param int $length
	 * @param bool $add_dashes
	 * @param string $available_sets
	 * @return string 
	 */
	function generate($length = 9, $add_dashes = false, $available_sets = 'luds') {
		$sets = array();
		if (strpos($available_sets, 'l') !== false)
			$sets[] = 'abcdefghjkmnpqrstuvwxyz';
		if (strpos($available_sets, 'u') !== false)
			$sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
		if (strpos($available_sets, 'd') !== false)
			$sets[] = '23456789';
		if (strpos($available_sets, 's') !== false)
			$sets[] = '!@#$%&*?';

		$all = '';
		$password = '';
		foreach ($sets as $set) {
			$password .= $set[array_rand(str_split($set))];
			$all .= $set;
		}

		$all = str_split($all);
		for ($i = 0; $i < $length - count($sets); $i++) {
			$password .= $all[array_rand($all)];
		}

		$password = str_shuffle($password);

		if (!$add_dashes) {
			return $password;
		}

		$dash_len = floor(sqrt($length));
		$dash_str = '';
		while (strlen($password) > $dash_len) {
			$dash_str .= substr($password, 0, $dash_len) . '-';
			$password = substr($password, $dash_len);
		}
		$dash_str .= $password;
		return $dash_str;
	}

	/**
	 * Check password strength (1 to 5)
	 * 
	 * @param string $password
	 * @return int 
	 */
	function check_strength($password) {
		$score = 1;

		if (strlen($pwd) < 1) {
			return $strength[0];
		}
		if (strlen($pwd) < 4) {
			return $strength[1];
		}

		if (strlen($pwd) >= 8) {
			$score++;
		}
		if (strlen($pwd) >= 10) {
			$score++;
		}

		if (preg_match("/[a-z]/", $pwd) && preg_match("/[A-Z]/", $pwd)) {
			$score++;
		}
		if (preg_match("/[0-9]/", $pwd)) {
			$score++;
		}
		if (preg_match("/.[!,@,#,$,%,^,&,*,?,_,~,-,£,(,)]/", $pwd)) {
			$score++;
		}

		return $score;
	}

	/**
	 * Generate a random string of given length
	 * @param type $length
	 * @return string 
	 */
	static function random_string($length = '10') {
		$alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$alphabet_length = strlen($alphabet);
		$output = '';

		for ($i = 0; $i < $length; $i++) {
			$output .= $alphabet[rand(0, $alphabet_length - 1)];
		}

		return $output;
	}

	/**
	 * Hash a password, gives a random hash
	 * 
	 * @param string $password
	 * @param string $salt
	 * @return string
	 */
	static function hash($password, $salt = null) {
		if (!$salt) {
			$salt = self::random_string(10);
		}
		$sha1 = sha1($salt . $password);
		for ($i = 0; $i < 1000; $i++) {
			$sha1 = sha1($sha1 . (($i % 2 == 0) ? $password : $salt));
		}
		return $salt . '#' . $sha1;
	}

	/**
	 * Check if a password is valid
	 * 
	 * @param string $password
	 * @param string $hash
	 * @return bool 
	 */
	static function check_hash($password, $hash) {
		$salt = substr($hash, 0, 10);
		if (self::hash($password, $salt) == $hash) {
			return true;
		}
		return false;
	}

}