<?php

namespace k\db\orm;

/**
 * Password
 */
trait Password {

	public static $passwordFields = array(
		'password' => 'VARCHAR(255)',
	);

	public function set_password($password) {
		$this->password = password_hash($password);
		return $this;
	}

	public function generatePassword($length = 10, $chars = 'abcdefghjkpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXY3456789') {
		$password = substr(str_shuffle($chars), 0, $length);
		$this->password = $password;
		return $password;
	}

}