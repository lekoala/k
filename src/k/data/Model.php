<?php

namespace k\data;

use \JsonSerializable;
use \InvalidArgumentException;
use \ArrayAccess;
use \Iterator;
use \ReflectionClass;

/**
 * POPO Model
 *
 * @author lekoala
 */
class Model implements JsonSerializable, ArrayAccess, Iterator {

	const META_TYPE = 'type';
	const META_RULES = 'rules';
	const META_EXPORT = 'export';

	/**
	 * Store meta information about fields
	 * 
	 * Example
	 * 
	 * [
	 * 	'id' => [
	 * 		'type' => 'int',
	 * 		'rules' => ['required','integer'],
	 * 		'export' => true
	 * 	],
	 * 'name' => [
	 * 		'type' => 'string',
	 * 		'rules' => ['required','minlength' => 3],
	 * 		'export' => true
	 * 	]
	 * 	'created_at' => [
	 * 		'type' => 'datetime',
	 * 		'export' => false
	 * 	]
	 * ]
	 * 
	 * @var array 
	 */
	protected static $_meta = [];

	/**
	 * Extract information from _meta
	 * @param string $key
	 * @return array
	 */
	public static function getMeta($key, $flat = false) {
		$meta = [];
		foreach ($meta as $k => $v) {
			if (isset($v[$key])) {
				$meta[$k] = $v[$key];
			}
		}
		if ($flat) {
			$arr = [];
			foreach ($meta as $k => $v) {
				if ($v) {
					$arr[] = $k;
				}
			}
			return $arr;
		}
		return $meta;
	}

	/**
	 * Alias for getMeta('export',true)
	 * @return array
	 */
	public static function getExport() {
		return self::getMeta(self::META_EXPORT, true);
	}

	/**
	 * Alias for getMeta('type')
	 * @return array
	 */
	public static function getType() {
		return self::getMeta(self::META_TYPE);
	}

	/**
	 * Alias for getMeta('rules')
	 * @return array
	 */
	public static function getRules() {
		return self::getMeta(self::META_RULES);
	}

	/**
	 * Get reflection class
	 * 
	 * @staticvar \k\data\ReflectionClass $refl
	 * @return \k\data\ReflectionClass
	 */
	public static function getReflectedClass() {
		static $refl;

		if ($refl === null) {
			$refl = new ReflectionClass(get_called_class());
		}

		return $refl;
	}

	/**
	 * Enum list values based on class constants. 
	 * 
	 * For instance
	 * class::enum('meta') will return ['type','rules','export']
	 * 
	 * @staticvar array $constants
	 * @param string $name
	 * @return array
	 */
	public static function enum($name) {
		static $constants;

		$name = strtoupper($name);

		if ($constants === null) {
			$ref = static::getReflectedClass();
			$constants = $ref->getConstants();
		}

		$enum = array();
		foreach ($constants as $key => $value) {
			if (strpos($key, $name) === 0) {
				$key = strtolower(str_replace($name . '_', '', $key));
				$enum[$key] = $value;
			}
		}
		return $enum;
	}

	/**
	 * Validate the records. Throw exception if not valid
	 * @param array $rules
	 */
	public function validate($rules = null) {
		if ($rules === null) {
			$rules = static::getRules();
		}
		$validator = new Validator($this->getFields(), $rules);
		$validator->validate(true);
	}

	/**
	 * Verify is model has a field
	 * 
	 * @param type $name
	 * @return boolean
	 */
	public function has($name) {
		if (property_exists($this, $name)) {
			return true;
		}
	}

	/**
	 * Get field value
	 * 
	 * @param type $name
	 * @return type
	 */
	public function get($name) {
		$v = null;
		if (property_exists($this, $name)) {
			$v = $this->$name;
		}
		return $v;
	}

	/**
	 * Set field value
	 * 
	 * @param type $name
	 * @param type $value
	 * @return type
	 */
	public function set($name, $value) {
		if (property_exists($this, $name)) {
			return $this->$name = $value;
		}
	}

	public function __get($name) {
		return $this->get($name);
	}

	public function __set($name, $value) {
		return $this->set($name, $value);
	}

	/**
	 * Set public fields
	 * 
	 * @param array $fields
	 */
	public function setFields($fields) {
		foreach ((array) $fields as $f => $v) {
			$this->$f = $v;
		}
	}

	/**
	 * Get all public fields
	 * 
	 * @return array
	 */
	public function getFields() {
		$getFields = function($obj) {
					return get_object_vars($obj);
				};
		return $getFields($this);
	}

	/* --- JsonSerializable --- */

	/**
	 * Export object to an array
	 * 
	 * @param string|array $fields Fields to export
	 * @return array
	 */
	public function toArray($fields = null) {
		if (empty($fields)) {
			$fields = array_keys(static::getFields());
		}
		$arr = array();
		if (is_string($fields)) {
			$fields = explode(',', $fields);
		}
		foreach ($fields as $f) {
			$arr[$f] = $this->get($f);
		}
		return $arr;
	}

	/**
	 * Convert the object in array for json
	 * 
	 * @return array
	 */
	public function jsonSerialize() {
		return $this->toArray(static::getExport());
	}

	/* --- ArrayAccess --- */

	public function offsetSet($offset, $value) {
		$this->setField($offset, $value);
	}

	public function offsetExists($offset) {
		return $this->hasField($offset);
	}

	public function offsetUnset($offset) {
		if ($this->hasField($offset)) {
			$this->setField($offset, null);
		}
	}

	public function offsetGet($offset) {
		return $this->getField($offset);
	}

	/* --- Iterator --- */

	public function rewind() {
		reset($this->data);
	}

	public function current() {
		$var = current($this->data);
		return $var;
	}

	public function key() {
		$var = key($this->data);
		return $var;
	}

	public function next() {
		$var = next($this->data);
		return $var;
	}

	public function valid() {
		$key = key($this->data);
		$var = ($key !== null && $key !== false);
		return $var;
	}

}