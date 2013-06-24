<?php

namespace k\db\orm;

/**
 * Implements a simple binary permission system
 * @link http://codingrecipes.com/how-to-write-a-permission-system-using-bits-and-bitwise-operations-in-php
 */
trait Permissions {
	
	public static $fieldsPermissions = array(
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
	 * Check if the permission exists
	 * 
	 * @param string|int
	 * @return bool
	 */
	public function permissionExists($permission) {
		if(!is_int($permission)) {
			return array_key_exists($permission, self::getPermissions());
		}
		return in_array($permission, array_values(self::getPermissions()));
	}
	
	/**
	 * Does the user has this perm / theses perms
	 * 
	 * @param string|array $permission
	 * @return string
	 */
	public function hasPermission($permission) {
		if (is_array($permission)) {
			foreach ($permission as $p) {
				if (!$this->hasPermission($p)) {
					return false;
				}
			}
			return true;
		}
		
		if(!is_int($permission)) {
			$systemPermissions = self::getPermissions();
			$permission = $systemPermissions[$permission];
		}
		
		if ($this->perms & $permission) {
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
		$this->perms = 0;
		foreach($systemPermissions as $k => $v) {
			$this->addPermission($v);
		}
		return $this->perms;
	}

	/**
	 * Add a permission
	 * @param string $permission 
	 * @return string
	 */
	public function addPermission($permission) {
		if (is_array($permission)) {
			foreach ($permission as $p) {
				$userPermissions = $this->addPermission($p);
			}
			return $userPermissions;
		}
		if(!is_int($permission)) {
			$systemPermissions = self::getPermissions();
			$permission = $systemPermissions[$permission];
		}
		$this->perms |= $permission;
		return $this->perms;
	}
	
	/**
	 * Remove all permissions
	 * @return int
	 */
	public function removeAllPermissions() {
		$this->perms = 0;
		return 0;
	}

	/**
	 * Remove a permission
	 * @param string $permissions 
	 * @return string
	 */
	public function removePermission($permission) {
		if (is_array($permission)) {
			foreach ($permission as $p) {
				$userPermissions = $this->emovePermission($p);
			}
			return $userPermissions;
		}
		if(!is_int($permission)) {
			$systemPermissions = self::getPermissions();
			$permission = $systemPermissions[$permission];
		}
		$this->perms ^= $permission;
		return $this->perms;
	}

	/**
	 * Read one permission
	 */
	public function readPermission($permission) {
		$systemPermissions = self::getPermissions();
		foreach ($systemPermissions as $k => $v) {
			if($v == $permission) {
				return $k;
			}
		}
		return false;
	}
	
	/**
	 * Decode the binary permissions to an array
	 * @return array
	 */
	public function readPermissions() {
		$systemPermissions = self::getPermissions();
		$p = array();
		foreach ($systemPermissions as $k => $v) {
			if ($this->hasPermission($v)) {
				$p[] = $k;
			}
		}
		return $p;
	}

}