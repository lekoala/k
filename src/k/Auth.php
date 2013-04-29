<?php

namespace k;

/**
 * Auth
 *
 * @author lekoala
 */
class Auth {

	protected $public = array();
	protected $restricted = array();
	protected $loginPage = '/auth/login';
	protected $logoutPage = '/auth/logout';
	
	public function __construct() {
		
	}
	
	public function getWhitelist() {
		return $this->whitelist;
	}

	public function setWhitelist($whitelist) {
		$this->whitelist = $whitelist;
		return $this;
	}

	public function getBlacklist() {
		return $this->blacklist;
	}

	public function setBlacklist($blacklist) {
		$this->blacklist = $blacklist;
		return $this;
	}
	
	public function getLoginPage() {
		return $this->loginPage;
	}

	public function setLoginPage($loginPage) {
		$this->loginPage = $loginPage;
		return $this;
	}

	public function getLogoutPage() {
		return $this->logoutPage;
	}

	public function setLogoutPage($logoutPage) {
		$this->logoutPage = $logoutPage;
		return $this;
	}
	
	public function verify($url) {
		//start with blacklist
		foreach($this->blacklist as $rule) {
			
		}
	}

}