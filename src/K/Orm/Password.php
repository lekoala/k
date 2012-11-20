<?php

namespace K\Orm;

/**
 * Password
 */
trait Password {
	protected $password;
	
	public function setPassword($password) {
		$this->password = \K\Password::hash($password);
		return $this;
	}
}