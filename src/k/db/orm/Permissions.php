<?php

namespace k;

/**
 * Implements a simple binary permission system
 */
trait Behaviour_Permissions {
	/**
	 * Store the permissions
	 * @var string
	 */
	protected $permissions;
	
	public static function getPermissions() {
		throw new Exception('You must implement getPermissions() to use this trait');
	}

	/**
	 * Does the user has this perm / theses perms
	 * 
	 * @param string|array $permission
	 * @return string
	 */
	public function hasPermission($permission) {
		$systemPermissions = self::getPermissions();
		if (is_array($permission)) {
			foreach ($permission as $p) {
				if (!self::hasPermission($permission, $this->permissions)) {
					return false;
				}
			}
			return true;
		}
		if ($this->permissions & $systemPermissions[$permission]) {
			return true;
		}
		return false;
	}

	/**
	 * Add a permission
	 * @param string $permission 
	 * @return string
	 */
	public function addPermission($permission) {
		$systemPermissions = self::getPermissions();
		if (is_array($permission)) {
			foreach ($permission as $p) {
				$userPermissions = self::addPermission($permission, $userPermissions);
			}
			return $userPermissions;
		}
		$this->permissions |= $systemPermissions[$permission];
		return $this->permissions;
	}

	/**
	 * Remove a permission
	 * @param string $permissions 
	 * @return string
	 */
	public function removePermission($permission) {
		$systemPermissions = self::getPermissions();
		if (is_array($permission)) {
			foreach ($permission as $p) {
				$userPermissions = self::removePermission($permission, $userPermissions);
			}
			return $userPermissions;
		}
		$this->permissions ^= $systemPermissions[$permission];
		return $this->permissions;
	}

	/**
	 * Decode the binary permissions to an array
	 * @return array
	 */
	public function readPermissions() {
		$systemPermissions = self::getPermissions();
		$p = array();
		foreach ($systemPermissions as $k => $v) {
			if ($this->hasPermission($k)) {
				$p[] = $k;
			}
		}
		return $p;
	}

}