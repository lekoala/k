<?php

namespace k\sql\orm;

/**
 * Password
 */
trait Password {

	protected $password;

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