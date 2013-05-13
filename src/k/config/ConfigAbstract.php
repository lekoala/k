<?php

namespace k\config;

use \ArrayAccess;
use \Iterator;
use \Countable;

/**
 * ConfigAbstract
 *
 * @author lekoala
 */
abstract class ConfigAbstract implements ArrayAccess, Iterator, Countable, ConfigInterface {

	protected $data = array();

	/**
	 * Get a value from config, allowing / notation, for instance db/host
	 * 
	 * @param string $key
	 * @param mixed $default
	 * @return mixed 
	 */
	public function get($key, $default = null) {
		$loc = &$this->data;
		foreach (explode('/', $key) as $step) {
			if (isset($loc[$step])) {
				$loc = &$loc[$step];
			} else {
				return $default;
			}
		}
		return $loc;
	}

	/**
	 * Dump data as array
	 * 
	 * @return array
	 */
	public function toArray() {
		return $this->data;
	}

	/**
	 * Merge configs
	 * 
	 * @param object|array $config
	 * @return array The merged data
	 */
	public function merge($config) {
		if (is_object($config) && method_exists($config, 'toArray')) {
			$config = $config->toArray();
		}
		$this->data = array_replace_recursive($this->data, $config);
		return $this->data;
	}
	
	///////////////////////////
	// Implements interfaces //

	public function offsetSet($offset, $value) {
		if (is_null($offset)) {
			$this->data[] = $value;
		} else {
			$this->data[$offset] = $value;
		}
	}

	public function offsetExists($offset) {
		return array_key_exists($offset, $this->data);
	}

	public function offsetUnset($offset) {
		unset($this->data[$offset]);
	}

	public function offsetGet($offset) {
		return array_key_exists($offset, $this->data) ? $this->data[$offset] : null;
	}

	public function rewind() {
		reset($this->data);
	}

	public function current() {
		return current($this->data);
	}

	public function key() {
		return key($this->data);
	}

	public function next() {
		return next($this->data);
	}

	public function valid() {
		return $this->current() !== false;
	}

	public function count() {
		return count($this->data);
	}

}