<?php

namespace k\config;

/**
 * Simple config wrapper
 * 
 * Load a .php file that returns an array.
 */
class PhpConfig extends ConfigAbstract {
	
	/**
	 * Path to file
	 * @var string
	 */
	protected $file;

	/**
	 * Create a new config object. You can pass a file or an array
	 * to load into the config object.
	 * @param string|array $file
	 */
	public function __construct($file = null) {
		if ($file) {
			$this->load($file);
		}
	}

	/**
	 * Load	a config file or array into the wrapper
	 * @param string|array $file
	 */
	public function load($file) {
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
			
			$config = require $file;
		}

		$this->data = array_replace_recursive($this->data, $config);

		return $this->data;
	}

	/**
	 * Get file path
	 * @return string
	 */
	public function getFile() {
		return $this->file;
	}
	
}