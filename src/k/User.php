<?php

namespace K;

class User {
	
	static $permissions;
	static $defaultLang;
	static $defaultLangKey;
	static $table;
	static $usernameField;
	static $passwordField;
	
	protected $perms;
	protected $lang;
	
	function getLang() {
		
	}
	
	
	function authenticate($username, $password) {
		$user = self::get_user($username);
		if($user) {
			if(password::check_hash($password, $user[$this->password_field])) {
				self::$current_user = $user;
				self::on_login();
				return true;
			}
		}
		return false;
	}
	
	static protected function on_login() {
		
	}
	
	static function get_user($username) {
		
	}
	
	/**
	 * Does the user has this perm / theses perms
	 * 
	 * @param string|array $perm
	 * @return string
	 */
	function has_perm($perm) {
		$perms = config::perms();
		$perm = arrayutils::make($perm);
		foreach($perm as $p) {
			if ($this->perms & $perms[$p]) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Add a permission
	 * @param string $perm 
	 */
	function add_perm($perm) {
		$perms = config::perms();
		$perm = arrayutils::make($perm);
		foreach($perm as $p) {
			$this->perms |= $perms[$p];
		}
	}

	/**
	 * Remove a permission
	 * @param string $perm 
	 */
	function remove_perm($perm) {
		$perms = config::perms();
		$perm = arrayutils::make($perm);
		foreach($perm as $p) {
			$this->perms ^= $perms[$p];
		}
	}
}