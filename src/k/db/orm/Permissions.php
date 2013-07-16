<?php

namespace k\db\orm;

/**
 * Implements a simple binary permission system
 * @link http://codingrecipes.com/how-to-write-a-permission-system-using-bits-and-bitwise-operations-in-php
 */
trait Permissions {

	public $perms;

	public static function getDefaultPermissions() {
		return array(
			'view' => 1,
			'comment' => 2 << 0,
			'post' => 2 << 1,
			'edit' => 2 << 2,
			'delete' => 2 << 3,
			'admin' => 2 << 4,
			'developer' => 2 << 5
		);
	}

	public function onPreSavePermissions() {
		//recompute permissions
		$perms = $this->perms;
		if (strpos($perms, ',') !== false) {
			$perms = explode(',', $perms);
			$this->perms = 0;
			foreach ($perms as $perm) {
				$this->addPermission($perm);
			}
		} else {
			$this->perms = $this->_original['perms'];
		}
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
		if (!is_numeric($permission)) {
			return array_key_exists($permission, static::getPermissions());
		}
		return in_array($permission, array_values(static::getPermissions()));
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

		if (!is_numeric($permission)) {
			$systemPermissions = static::getPermissions();
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
		$systemPermissions = static::getPermissions();
		$this->perms = 0;
		foreach ($systemPermissions as $k => $v) {
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
		if (!is_numeric($permission)) {
			$systemPermissions = static::getPermissions();
			if (!isset($systemPermissions[$permission])) {
				throw new \Exception('Permission ' . $permission . ' does not exists');
			}
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
		if (!is_numeric($permission)) {
			$systemPermissions = static::getPermissions();
			$permission = $systemPermissions[$permission];
		}
		$this->perms ^= $permission;
		return $this->perms;
	}

	/**
	 * Read one permission
	 */
	public function readPermission($permission) {
		$systemPermissions = static::getPermissions();
		if (!is_numeric($permission)) {
			if (!isset($systemPermissions[$permission])) {
				throw new \Exception('Permission ' . $permission . ' does not exists');
			}
			return $systemPermissions[$permission];
		}
		foreach ($systemPermissions as $name => $code) {
			if ($code == $permission) {
				return $name;
			}
		}
		throw new \Exception('Permission ' . $permission . ' does not exists');
	}

	/**
	 * Decode the binary permissions to an array
	 * @return array
	 */
	public function readPermissions($text = true) {
		$systemPermissions = static::getPermissions();
		$p = array();
		foreach ($systemPermissions as $k => $v) {
			if ($this->hasPermission($v)) {
				if ($text) {
					$p[] = $k;
				} else {
					$p[] = $v;
				}
			}
		}
		return $p;
	}

}