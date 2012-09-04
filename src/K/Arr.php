<?php

namespace K;

class Arr {

	/**
	 * Get a value from an array
	 * 
	 * @param array $array
	 * @param string $index
	 * @param mixed $default 
	 */
	static function index(array $array, $index, $default = null) {
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
	static function get(array $array, $index, $default = null) {
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
	 * Set a value in an array, allowing dot notation
	 * 
	 * @param array $array
	 * @param string $index
	 * @param mixed $value
	 * @return mixed 
	 */
	static function set(array $array, $index, $value) {
		$loc = &$array;
		foreach (explode('.', $index) as $step) {
			$loc = &$loc[$step];
		}
		return $loc = $value;
	}

	/**
	 * Convert a var to array
	 * 
	 * @param mixed $var
	 * @param string $delimiter (for string parameters)
	 * @param bool $trim (for string parameters)
	 * @return array 
	 */
	static function make($var) {
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
				return $var->to_array();
			}
			return get_object_vars($var);
		}
		throw new exception('Make does not support objects of type ' . gettype($var));
	}

	/**
	 * Return a list of elements matching the index
	 * 
	 * @param array $array
	 * @param string $index
	 * @return array 
	 */
	static function ls(array $array, $index) {
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
	static function isAssoc(array $array) {
		//don't use array_keys or array_values because it takes a lot of memory for large arrays
		foreach ($array as $k => $v) {
			if (!is_int($k)) {
				return true;
			}
		}
		return false;
	}

}