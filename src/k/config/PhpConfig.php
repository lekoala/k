<?php

namespace k\config;

/**
 * Simple config wrapper
 * 
 * Load a .php file that returns an array.
 * Override values if you have a .local.php files.
 */
class PhpConfig extends ConfigAbstract {
	
	/**
	 * Path to file
	 * @var string
	 */
	protected $file;

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
	 * Does the config object loaded a .local file
	 * @return bool
	 */
	public function hasLocal() {
		return isset($this->localData);
	}
	
	/**
	 * Get local config data
	 * @return array
	 */
	public function getLocalData() {
		if(!$this->hasLocal()) {
			return array();
		}
		return $this->localData;
	}
	
	/**
	 * Get original config data
	 * @return array
	 */
	public function getOriginalData() {
		return $this->originalData;
	}
	
	/**
	 * Get file path
	 * @return string
	 */
	public function getFile() {
		return $this->file;
	}
	
	/**
	 * Get a value from config, allowing / notation, for instance db/host
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


}