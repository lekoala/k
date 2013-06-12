<?php

namespace k\db\orm;

/**
 * Implements a simple binary permission system
 * @link http://codingrecipes.com/how-to-write-a-permission-system-using-bits-and-bitwise-operations-in-php
 */
trait Permissions {
	
	public static $permissionsFields = array(
		'perms' => 'INT'
	);
	
	public static function getDefaultPermissions() {
		return array(
			'view' => 1,
			'comment' => 2,
			'post' => 4,
			'edit' => 8,
			'delete' => 16,
			'admin' => 32,
			'developer' => 64
		);
	}
	
	public static function getPermissions() {
		//feel free to implement something else in your model
		return static::getDefaultPermissions();
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
	 * Add all permissions
	 * @return string
	 */
	public function addAllPermissions() {
		$systemPermissions = self::getPermissions();
		$this->permissions = 0;
		foreach($systemPermissions as $k => $v) {
			$this->addPermission($k);
		}
		return $this->permissions;
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
	 * Remove all permissions
	 * @return int
	 */
	public function removeAllPermissions() {
		$this->permissions = 0;
		return 0;
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