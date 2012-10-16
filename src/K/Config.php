<?php

namespace K;

use \ArrayAccess;
use \Iterator;
use \Countable;

/**
 * Simple config wrapper
 * 
 * Load a .php file that returns an array.
 * Override values if you have a .local.php files.
 */
class Config implements ArrayAccess, Iterator, Countable {
	
	/**
	 * Path to file
	 * @var string
	 */
	protected $file;

	/**
	 * Internal config array
	 * @var array
	 */
	protected $data = array();

	/**
	 * Local data array
	 * @var array 
	 */
	protected $localData;

	/**
	 * Original data array
	 * @var array
	 */
	protected $originalData = array();

	/**
	 * Create a new config object. You can pass a file or an array
	 * to load into the config object.
	 * @param string|array $file
	 */
	function __construct($file = null) {
		if ($file) {
			$this->load($file);
		}
	}

	/**
	 * Load	a config file or array into the wrapper
	 * @param string|array $file
	 */
	function load($file) {
		if (is_object($file) && method_exists($file, 'toArray')) {
			$file = $file->toArray();
		}
		if (is_array($file)) {
			$config = $file;
		} else {
			if (!is_file($file)) {
				throw new Exception('File does not exist : ' . $file);
			}
			
			$ext = pathinfo($file, PATHINFO_EXTENSION);
			if ($ext != 'php') {
				throw new Exception('Invalid config file');
			}
			$this->file = $file;
			
			$filename = str_replace('.php', '', $file);

			// Check for local config file
			$localFile = $filename . '.local' . '.php';
			$config = require $file;
			if (!is_array($config)) {
				throw new Exception('Config file does not return an array');
			}
			$this->originalData = $config;

			if (is_file($localFile)) {
				$localConfig = require $localFile;
				if (!is_array($localConfig)) {
					throw new Exception('Config file does not return an array');
				}
				$this->localData = $localConfig;
				$config = array_replace_recursive($config, $localConfig);
			}
		}

		$this->data = array_replace_recursive($this->data, $config);

		if (isset($this->data['config'])) {
			$this->configure($this->data['config']);
		}

		return $this->data;
	}

	/**
	 * Dump data as array
	 * @return array
	 */
	function toArray() {
		return $this->data;
	}

	/**
	 * Does the config object loaded a .local file
	 * @return bool
	 */
	function hasLocal() {
		return isset($this->localData);
	}
	
	/**
	 * Get local config data
	 * @return array
	 */
	function getLocalData() {
		if(!$this->hasLocal()) {
			return array();
		}
		return $this->localData;
	}
	
	/**
	 * Get original config data
	 * @return array
	 */
	function getOriginalData() {
		return $this->originalData;
	}
	
	/**
	 * Get file path
	 * @return string
	 */
	function getFile() {
		return $this->file;
	}
	
	/**
	 * Get a value from config, allowing dot notation, for instance db.host
	 * @param string $key
	 * @param mixed $default
	 * @return mixed 
	 */
	function get($key, $default = null) {
		$loc = &$this->data;
		foreach (explode('.', $key) as $step) {
			if (isset($loc[$step])) {
				$loc = &$loc[$step];
			} else {
				return $default;
			}
		}
		return $loc;
	}

	// --- Implements ArrayAccess, Iterator and Countable --- //

	public function offsetSet($offset, $value) {
		if (is_null($offset)) {
			$this->container[] = $value;
		} else {
			$this->container[$offset] = $value;
		}
	}

	public function offsetExists($offset) {
		return isset($this->data[$offset]);
	}

	public function offsetUnset($offset) {
		unset($this->data[$offset]);
	}

	public function offsetGet($offset) {
		return isset($this->data[$offset]) ? $this->data[$offset] : null;
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