<?php

namespace K;

/**
 * Simple template
 */
class Template {
	
	use TConfigure;
	
	static protected $globalVars = array();
	static protected $defaultExtension = 'phtml';
	static protected $defaultPath;
	protected $filename;
	protected $vars;

	public function __construct($filename, $vars) {
		if (is_array($filename)) {
			while (count($filename) > 1) {
				$template = array_shift($filename);
				$vars = array('content' => new Template($template, $vars));
			}
			$filename = array_shift($filename);
		}
		if (strpos($filename, '/') === 0) {
			$filename = self::$defaultPath . $filename;
		}
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		if (!$ext) {
			$filename .= '.' . self::$defaultExtension;
		}
		if (!is_file($filename)) {
			throw new Exception($filename . ' does not exist');
		}
		if (!is_array($vars)) {
			$vars = array('content' => $vars);
		}

		$this->filename = $filename;
		$this->vars = $vars;
	}
	
	public function e($name) {
		echo $this->vars[$name];
	}
	
	public function t($name) {
		
	}

	/**
	 * Get a global value or all global value
	 * @param string $k
	 * @return mixed
	 */
	public static function getGlobals($k = null) {
		if ($k === null) {
			return self::$globalVars;
		}
		return isset(self::$globalVars[$k]) ? self::$globalVars[$k] : null;
	}

	/**
	 * Set a global value or add an array of global values
	 * @param string|array $k
	 * @param mixed $v
	 * @return string
	 */
	public static function setGlobals($k, $v = null) {
		if (!$v) {
			return self::$globalVars = array_merge(self::$globalVars, $k);
		}
		return self::$globalVars[$k] = $v;
	}

	/**
	 * Unset a global value or reset all global values
	 * @param string $k
	 * @return boolean
	 */
	public static function unsetGlobals($k = null) {
		if ($k) {
			unset(self::$globalVars[$k]);
			return true;
		}
		self::$globalVars = array();
	}

	/**
	 * Render the template
	 * @return string
	 */
	public function render() {
		extract(array_merge($this->vars, self::$globalVars), EXTR_REFS);

		ob_start();
		include($this->filename);
		$output = ob_get_clean();

		return $output;
	}

	/**
	 * Implement toString
	 * @return string
	 */
	public function __toString() {
		try {
			return $this->render();
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}

}