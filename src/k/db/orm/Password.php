<?php

namespace k\db\orm;

/**
 * Passwor trait
 * 
 * Require PHP 5.5 or password-compat
 */
trait Password {

	public static $fieldsPassword = array(
		'password' => 'VARCHAR(255)',
	);

	public function set_password($password) {
		$this->setRawField('password',password_hash($password,PASSWORD_BCRYPT));
		return $this;
	}

	public function generatePassword($length = 10, $chars = 'abcdefghjkpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXY3456789') {
		$password = substr(str_shuffle($chars), 0, $length);
		$this->password = $password;
		return $password;
	}

}