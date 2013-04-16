<?php

namespace k;

class Arr {
	
	private function __construct() {
	}

	/**
	 * Get a value from an array
	 * 
	 * @param array $array
	 * @param string|array $index
	 * @param mixed $default 
	 */
	public static function index(array $array, $index, $default = null) {
		if(is_array($index)) {
			$arr = $array;
			$val = $default;
			foreach($index as $i) {
				if(isset($arr[$i])) {
					$val = $arr[$i];
					if(is_array($val)) {
						$arr = $val;
					}
				}
				else {
					return $default;
				}
			}
			return $val;
		}
		if (isset($array[$index])) {
			return $array[$index];
		}
		return $default;
	}

	/**
	 * Get a value from array, allowing dot notation
	 * 
	 * @param array $array
	 * @param string $index
	 * @param mixed $default
	 * @return mixed 
	 */
	public static function get($array, $index, $default = null) {
		$loc = &$array;
		foreach (explode('.', $index) as $step) {
			if (isset($loc[$step])) {
				$loc = &$loc[$step];
			} else {
				return $default;
			}
		}
		return $loc;
	}

	/**
	 * Set a value in an array, allowing / notation
	 * 
	 * @param array $array
	 * @param string $index
	 * @param mixed $value
	 * @return array 
	 */
	public static function set(&$array, $index, $value) {
		$loc = &$array;
		foreach (explode('/', $index) as $step) {
			$loc = &$loc[$step];
		}
		$loc = $value;
		return $array;
	}
	
	/**
	 * Does this array has the needle
	 * 
	 * @param array $array
	 * @param mixed $needle Pass a value or an array of values
	 * @param bool $exact Should this method behave like in_array
	 */
	public static function has($array, $needle, $exact = false) {
		if($exact) {
			return in_array($needle, $array);
		}
		if(is_array($needle)) {
			foreach($needle as $value) {
				if(self::has($array, $value)) {
					return true;
				}
			}
		}
		return in_array($needle, $array);
	}
	
	/**
	 * Delete a value, allowing / notation
	 * 
	 * @param array $array
	 * @param string $index
	 * @return array
	 */
	public static function delete(&$array, $index) {
		$loc = &$array;
		$parts = explode('/', $index);
		while(count($parts) > 1) {
			$step = array_shift($parts);
			if(!isset($loc[$step])) {
				return false;
			}
			$loc = &$loc[$step];
		}
		unset($loc[array_shift($parts)]);
		return $array;
	}

	/**
	 * Convert a var to array
	 * 
	 * @param mixed $var
	 * @param string $delimiter (for string parameters)
	 * @param bool $trim (for string parameters)
	 * @return array 
	 */
	public static function make($var, $delimiter = ',', $trim = false) {
		if (is_array($var)) {
			return $var;
		}
		if (empty($var)) {
			return array();
		}
		if (is_string($var)) {
			$array = explode($delimiter, $var);
			if ($trim) {
				array_walk($array, 'trim');
			}
			return $array;
		}
		if (is_object($var)) {
			if (method_exists($var, 'toArray')) {
				return $var->toArray();
			}
			return get_object_vars($var);
		}
		throw new Exception('Make does not support objects of type ' . gettype($var));
	}

	/**
	 * Return a list of elements matching the index
	 * 
	 * @param array $array
	 * @param string $index
	 * @return array 
	 */
	public static function pluck(array $array, $index) {
		$list = array();
		foreach ($array as $row) {
			if (is_array($row)) {
				if (isset($row[$index])) {
					$list[] = $row[$index];
				}
			} elseif (is_object($row)) {
				if (isset($row->$index)) {
					$list[] = $row->$index;
				}
			}
		}
		return $list;
	}

	/**
	 * Is associative array
	 * 
	 * @param array $arr
	 * @return bool
	 */
	public static function isAssoc(array $array) {
		//don't use array_keys or array_values because it takes a lot of memory for large arrays
		foreach ($array as $k => $v) {
			if (!is_int($k)) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Is multi dimensional array
	 * 
	 * @param array $array
	 * @return bool
	 */
	public static function isMulti(array $array) {
		$values = array_filter($arr, 'is_array');
		return $all ? count($arr) === count($values) : count($values) > 0;
	}
	
	/**
	 * Get a random element of an array
	 * @param array $array
	 * @return mixed
	 */
	public static function random(array $array) {
		return $array[array_rand($array)];
	}

}