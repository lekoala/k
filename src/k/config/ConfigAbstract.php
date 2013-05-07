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
	
	abstract public function get($key,$default = null);
	
	/**
	 * Dump data as array
	 * @return array
	 */
	public function toArray() {
		return $this->data;
	}
	
	public function offsetSet($offset, $value) {
		if (is_null($offset)) {
			$this->data[] = $value;
		} else {
			$this->data[$offset] = $value;
		}
	}

	public function offsetExists($offset) {
		return array_key_exists($offset,$this->data);
	}

	public function offsetUnset($offset) {
		unset($this->data[$offset]);
	}

	public function offsetGet($offset) {
		return array_key_exists($offset,$this->data) ? $this->data[$offset] : null;
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