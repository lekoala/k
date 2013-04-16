<?php

namespace k\db\orm;

/**
 * Password
 */
trait Password {
	protected $password;
	
	public function setPassword($password) {
		$this->password = \K\Password::hash($password);
		return $this;
	}
	
	public function generatePassword($length = 10, $chars = 'abcdefghjkpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXY3456789') {
		$password = substr( str_shuffle( $chars ), 0, $length );
		$this->setPassword($password);
		return $password;
	}
}