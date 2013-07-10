<?php

namespace k\data;

use \JsonSerializable;
use \ArrayAccess;
use \ReflectionClass;

/**
 * POPO Model supercharged :-)
 * 
 * Models properties should be declared as public allowing raw access if need be
 * If you want to use model feature, you can use get()/set() to use virtual properties and defaults
 *
 * @author lekoala
 */
class Model implements JsonSerializable, ArrayAccess {

	const IP = 'ip';
	const EMAIL = 'email';
	const URL = 'url';
	const DIGITS = 'digits';
	const NUMBER = 'number';
	const NUMERIC = 'numeric';
	const INTEGER = 'integer';
	const SLUG = 'slug';
	const ALPHA = 'alpha';
	const ALPHANUM = 'alphanum';
	const DATE_ISO = 'dateIso';
	const DATE = 'date';
	const TIME = 'time';
	const PHONE = 'phone';
	const LUHN = 'luhn'; //bool

	/**
	 * Store type information
	 * 
	 * Type will automatically add a validation rule 
	 * 
	 * Example
	 * 
	 * [
	 * 	'id' => 'int'
	 *  'name' => 'string'
	 * 	'created_at' => 'dateIso'
	 * ]
	 * 
	 * @var array 
	 */

	protected static $_types = [];

	/**
	 * Store validation rules
	 * 
	 * Example
	 * 
	 * [
	 * 	'id' => 'required',
	 * 	'name' => ['minlength' => 3]
	 * ]
	 * @var array
	 */
	protected static $_rules = [];

	/**
	 * Store defaults values
	 * 
	 * Example
	 * 
	 * [
	 * 	'name' => 'Some name'
	 * ]
	 * @var array
	 */
	protected static $_defaults = [];

	/**
	 * Declare which fields are exportable by default
	 * 
	 * @var array
	 */
	protected static $_exportable = [];

	/**
	 * Return defaults value
	 * 
	 * Defaults are checked at runtime, when accessing the property
	 * 
	 * @return array
	 */
	public static function getDefaults() {
		static $dyn;

		if ($dyn === null) {
			static::setDynamicDefaults();
			$dyn = true;
		}

		return static::$_defaults;
	}

	/**
	 * Set dynamic defaults here
	 */
	public static function setDynamicDefaults() {
		
	}

	/**
	 * Get types
	 * 
	 * @return array
	 */
	public static function getTypes() {
		return static::$_types;
	}

	/**
	 * Return exportable value
	 * 
	 * @return array
	 */
	public static function getExportable() {
		return static::$_exportable;
	}

	/**
	 * Return validation rules
	 * 
	 * @return array
	 */
	public static function getRules() {
		static $dyn;

		if ($dyn === null) {
			$types = static::getTypes();
			foreach ($types as $name => $type) {
				if (!isset(static::$_rules[$name])) {
					static::$_rules[$name] = $type;
				}
			}
			$dyn = true;
		}

		return static::$_rules;
	}

	/**
	 * Get reflection class
	 * 
	 * @staticvar \ReflectionClass $refl
	 * @return \ReflectionClass
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
	 * META_TYPE = 'type';
	 * META_RULES = 'rules';
	 * META_EXPORT = 'export';
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
	 * Get all traits applied to this object
	 * 
	 * Example
	 * 
	 * [
	 * 'some\trait' => 'trait'
	 * ]
	 * 
	 * @staticvar array $traits
	 * @return array
	 */
	public static function getTraits() {
		static $traits;

		if ($traits === null) {
			$ref = static::getReflectedClass();
			$ts = $ref->getTraitNames();
			$traits = array();
			foreach ($ts as $trait) {
				$name = explode('\\', $trait);
				$name = end($name);
				$traits[$trait] = $name;
			}
		}

		return $traits;
	}

	/**
	 * Get all declared public properties names
	 * 
	 * Properties added at runtime won't be visible here
	 * 
	 * @staticvar array $props
	 * @return array
	 */
	public static function getDeclaredPublicProperties() {
		static $props;

		if (!$props) {
			$refl = static::getReflectedClass();
			$prop = $refl->getProperties(\ReflectionProperty::IS_PUBLIC);
			foreach ($prop as $p) {
				$props[] = $p->getName();
			}
		}

		return $props;
	}

	/**
	 * Return a list of virtual getters
	 * 
	 * ['name' => 'get_name']
	 * 
	 * @staticvar array $props
	 * @return array
	 */
	public static function getVirtualGetters() {
		static $props;

		if (!$props) {
			$refl = static::getReflectedClass();
			$prop = $refl->getMethods(\ReflectionMethod::IS_PUBLIC);
			foreach ($prop as $p) {
				$name = $p->getName();
				if (strpos($name, 'get_') === 0) {
					$p = preg_replace('/^get_/', '', $name);
					$props[$p] = $name;
				}
			}
		}
		return $props;
	}

	/**
	 * Validate the records. Throw exception if not valid
	 * 
	 * @param array $rules
	 */
	public function validate($rules = null) {
		if ($rules === null) {
			$rules = static::getRules();
		}
		$validator = new Validator($this->getData(), $rules);
		$validator->validate(true);
	}

	/**
	 * Verify is model has a field or a virtual field
	 * 
	 * @param string $name
	 * @return boolean
	 */
	public function has($name) {
		if (property_exists($this, $name)) {
			return true;
		}
		$method = 'get_' . $name;
		if (method_exists($this, $method)) {
			return true;
		}
	}

	/**
	 * Get field or a virtual field value. A default value can be provided if empty
	 * 
	 * @param string $name
	 * @param string $format
	 * @return boolean
	 */
	public function get($name, $format = null) {
		$v = null;
		//virtual
		$method = 'get_' . $name;
		if (method_exists($this, $method)) {
			$v = $this->$method();
		}
		//instance
		elseif (property_exists($this, $name)) {
			$v = $this->$name;
		}
		//defaults
		if (empty($v)) {
			$defaults = static::getDefaults();
			if($defaults) {
				$v = isset($defaults[$name]) ? $defaults[$name] : null;
			}
			
		}
		return $v;
	}

	/**
	 * Set data value
	 * 
	 * You can set new properties that don't exist
	 * This method is chainable
	 * 
	 * @param string $name
	 * @param mixed $value
	 * @return static
	 */
	public function set($name, $value) {
		$method = 'set_' . $name;
		if (method_exists($this, $method)) {
			$this->$method($value);
		} else {
			$this->$name = $value;
		}
		return $this;
	}

	public function __get($name) {
		return $this->get($name);
	}

	public function __set($name, $value) {
		return $this->set($name, $value);
	}

	/**
	 * Get properties using virtual getters if they exists
	 * 
	 * @param array $data
	 * @return \k\data\Model
	 */
	public function getProperties() {
		$data = $this->getData(); //data include vars added at runtime
		//insert defaults
		$defaults = static::getDefaults();
		foreach ($data as $k => $v) {
			if (empty($v)) {
				$data[$k] = isset($defaults[$k]) ? $defaults[$k] : null;
			}
		}

		//add or override values with virtual getterss
		$virtual = static::getVirtualGetters();
		foreach ($virtual as $name => $v) {
			$data[$name] = $this->$v();
		}
		return $data;
	}

	/**
	 * Set properties using virtual setters if they exists
	 * 
	 * @param array $data
	 * @return \k\data\Model
	 */
	public function setProperties($data) {
		foreach ((array) $data as $f => $v) {
			$this->set($f, $v);
		}
		return $this;
	}

	/**
	 * Get all public properties
	 * 
	 * This is what you want if you want raw data. Otherwise, use getProperties()
	 * 
	 * @return array
	 */
	public function getData() {
		$pub = [];
		foreach ((array) $this as $k => $v) {
			if (strpos($k, "\0") === 0) {
				continue;
			}
			$pub[$k] = $v;
		}
		return $pub;
	}

	/**
	 * Set public properties
	 * 
	 * @param array $data
	 * @return \k\data\Model
	 */
	public function setData($data) {
		foreach ((array) $data as $f => $v) {
			$this->$f = $v;
		}
		return $this;
	}

	/* --- JsonSerializable --- */

	/**
	 * Export object to an array
	 * 
	 * @param string|array $fields Fields to export
	 * @return array
	 */
	public function toArray($fields = null) {
		if (is_string($fields)) {
			$fields = explode(',', $fields);
		}
		$arr = array();
		if (empty($fields)) {
			$fields = array_keys($this->getProperties());
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
		return $this->toArray(static::getExportable());
	}

	/* --- ArrayAccess --- */

	public function offsetSet($offset, $value) {
		$this->set($offset, $value);
	}

	public function offsetExists($offset) {
		return $this->has($offset);
	}

	public function offsetUnset($offset) {
		if ($this->has($offset)) {
			$this->set($offset, null);
		}
	}

	public function offsetGet($offset) {
		return $this->get($offset);
	}

}