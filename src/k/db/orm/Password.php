<?php

namespace k\db\orm;

/**
 * Passwor trait
 * 
 * Require PHP 5.5 or password-compat
 */
trait Password {

	protected $password;
	
	public function onPreSavePassword() {
		$org = $this->getOriginal();
		if($this->password != $org['password']) {
//			$this->set_password($this->password);
		}
	}

	public function set_password($password) {
		if($password == '') {
			return $this;
		}
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