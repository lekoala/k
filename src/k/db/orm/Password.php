<?php

namespace k\db\orm;

/**
 * Passwor trait
 * 
 * Require PHP 5.5 or password-compat
 */
trait Password {

	public $password;

	public function set_password($password) {
		$value = password_hash($password,PASSWORD_BCRYPT);
		$this->password = $value;
		return $this;
	}

	/**
	 * Generate a password
	 * 
	 * @param int $length
	 * @param string $chars
	 * @return string
	 */
	public function generatePassword($length = 10, $chars = 'abcdefghjkpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXY3456789') {
		$password = substr(str_shuffle($chars), 0, $length);
		$this->set_password($password);
		return $password;
	}

}