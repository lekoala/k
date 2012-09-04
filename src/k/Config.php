<?php

namespace K;

use \Exception;

/**
 * Simple config wrapper
 * 
 * Load a .php file that returns an array.
 * Override values if you have a .local.php files.
 */
class Config {
	const ENV_DEV = 'dev';
	const ENV_TEST = 'test';
	const ENV_PROD = 'prod';

	/**
	 * Internal config array
	 * 
	 * @var array
	 */
	protected $data = array();

	function __construct($file = null) {
		if ($file) {
			$this->load($file);
		}
	}

	/**
	 * Load	a config file or array into the wrapper
	 * 
	 * @param string|array $file
	 */
	function load($file) {
		if ($file instanceof config) {
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

			$filename = str_replace('.php', '', $file);

			// Check for local config file
			$local_file = $filename . '.local' . '.php';
			$config = require $file;
			if (!is_array($config)) {
				throw new Exception('Config file does not return an array');
			}

			if (is_file($local_file)) {
				$local_config = require $local_file;
				if (!is_array($local_config)) {
					throw new Exception('Config file does not return an array');
				}
				$config = array_replace_recursive($config, $local_config);
			}
		}

		$this->data = array_replace_recursive($this->data, $config);
		
		if(isset($this->data['config'])) {
			$this->configure($this->data['config']);
		}
		
		return $this->data;
	}

	/**
	 * Dump data as array
	 * 
	 * @return array
	 */
	function toArray() {
		return $this->data;
	}
	
	function configure($data) {
		foreach($data as $k => $v) {
			if(isset($this->$k)) {
				$this->$k = $v;
			}
		}
	}
	
	/**
	 * Get a value from config, allowing dot notation, for instance db.host
	 * 
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

}