<?php

namespace k\db\orm;

/**
 * Store Info info like name => value
 * Extra fields can be specified with the property $infoFields
 */
trait Info {

	protected $_infoCache;
	
	public static function getTableInfo() {
		return static::getTableName() . 'info';
	}

	public static function createTableInfo($execute = true) {
		$table = static::getTableName();
		$ttable = static::getTableInfo();
		$fields = array(
			'id',
			static::getForForeignKey(),
			'name',
			'value'
		);
		//TODO: add extra fields
		return static::getPdo()->createTable($ttable, $fields, array(), null, $execute);
	}

	public static function dropTableInfo() {
		return static::getPdo()->dropTable(static::getTableInfo());
	}

	/**
	 * Does the record have the info
	 * 
	 * @param string $name
	 * @param mixed $value
	 * @param boolean $cache
	 * @return boolean
	 */
	public function hasInfo($name = null, $value = null, $cache = false) {
		if(!$this->exists() || $cache) {
			if($this->_infoCache !== null) {
				if($name === null) {
					return $this->_infoCache !== null;
				}
				$v = isset($this->_infoCache[$name]) ? $this->_infoCache[$name] : null;
				if($value !== null) {
					return $v === $value;
				}
				return $v !== null;
			}
			return false;
		}
		$table = static::getTable();
		$ttable = static::getTableInfo();
		$where = array($table . '_id' => $this->id);
		if ($name) {
			$where['name'] = $name;
		}
		if ($value) {
			$where['value'] = $value;
		}
		return static::getPdo()->count($ttable, $where);
	}

	/**
	 * Add an info
	 * 
	 * @param string $name
	 * @param mixed $value
	 * @return boolean
	 */
	public function addInfo($name, $value) {
		$table = static::getTable();
		$ttable = static::getTableInfo();
		if (is_array($value)) {
			foreach ($value as $v) {
				$this->addInfo($name, $v);
			}
		}
		if (!$this->hasInfo($name, $value)) {
			if($this->_infoCache === null) {
				$this->_infoCache = [];
			}
			$this->_infoCache[$name] = $value;
			$this->pendingCallback(function() use ($ttable, $table, $name, $value) {
				return static::getPdo()->insert($ttable, array($table . '_id' => $this->id, 'name' => $name, 'value' => $value));
			});
			
		}
		return true;
	}

	/**
	 * Remove an info
	 * 
	 * @param string $name
	 * @param mixed $value
	 * @return boolean
	 */
	public function removeInfo($name = null, $value = null) {
		if(!$this->exists()) {
			return false;
		}
		$table = static::getTable();
		$ttable = static::getTableInfo();
		if (is_array($value)) {
			foreach ($value as $v) {
				$this->removeInfo($name, $v);
			}
		}
		$where = array($table . '_id' => $this->id);
		if ($name) {
			$where['name'] = $name;
		}
		if ($value) {
			$where['value'] = $value;
		}
		return static::getPdo()->delete($ttable, $where);
	}

	/**
	 * Get all associated infos. Cached by default
	 * 
	 * @param string $name
	 * @param boolean $cache
	 * @return array
	 */
	public function getInfo($name = null, $cache = true) {
		if(!$this->exists()) {
			return $this->_infoCache;
		}
		if($cache && $this->_infoCache !== null) {
			return $this->_infoCache;
		}
		$table = static::getTable();
		$ttable = static::getTableInfo();
		$where = array($table . '_id' => $this->id, 'name' => $name);
		$results = static::getPdo()->select($ttable, $where);
		$info = array();
		foreach ($results as $row) {
			if ($name) {
				$info[] = $row['value'];
			} else {
				if (!isset($info[$row['name']])) {
					$info[$row['name']] = array();
				}
				$info[$row['name']][] = $row['value'];
			}
		}
		$this->_infoCache = $info;
		return $info;
	}

}