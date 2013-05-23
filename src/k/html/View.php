<?php

namespace k\html;

use \InvalidArgumentException;
use \BadMethodCallException;

/**
 * Simple view wrapper
 */
class View {

	/**
	 * Path to the view to render
	 * @var string
	 */
	protected $filename;

	/**
	 * 
	 * Variables that will be made available to the view
	 * @var array
	 */
	protected $vars = array();
	
	/**
	 * Variables used by default by the view
	 * @var array
	 */
	protected $escapedVars = array();

	/**
	 * Store view helpers in an array
	 * @var array
	 */
	protected $helpers = array();
	
	/**
	 * Parent view
	 * @var View
	 */
	protected $parent = null;
	
	/**
	 * Create a new template. You can pass multiple filenames like array('page','layout')
	 * 
	 * @param string|array $filename
	 * @param array $vars
	 * @throws Exception
	 */
	public function __construct($filename, $vars = array()) {
		//recurse on template, each time including the view in the content variable
		if (is_array($filename)) {
			while (count($filename) > 1) {
				$template = array_shift($filename);
				$view = new View($template, $vars);
				$vars = array('content' => $view);
			}
			$filename = array_shift($filename);
		}
		$this->setFilename($filename);
		$this->setVars($vars);
	}

	public function getFilename() {
		return $this->filename;
	}

	public function setFilename($filename) {
		if (!is_file($filename)) {
			throw new InvalidArgumentException($filename . ' does not exist');
		}
		$this->filename = $filename;
		return $this;
	}
	
	public function getVar($name) {
		if(array_key_exists($name,$this->vars)) {
			return $this->vars[$name];
		}
		return '{{' . $name . '}}';
	}

	public function getVars() {
		return $this->vars;
	}

	public function setVars($vars, $clean = true) {
		if (!is_array($vars)) {
			$vars = array('content' => $vars);
		}
		if($clean) {
			$this->vars = array();
		}
		foreach($vars as $k => $v) {
			$this->setVar($k, $v);
		}
		return $this;
	}

	public function setVar($k, $v) {
		$this->vars[$k] = $v;
		return $this;
	}

	public function addVars($vars) {
		$this->setVar($vars, false);
		return $this;
	}

	public function getHelpers() {
		return $this->helpers;
	}

	public function setHelpers($helpers) {
		$this->helpers = $helpers;
		return $this;
	}

	public function addHelper($name, $helper) {
		$this->helpers[$name] = $helper;
		return $this;
	}
	
	public function getParent() {
		return $this->parent;
	}

	public function setParent(View $parent) {
		$this->parent = $parent;
		$this->parent->content = $this;
		return $this;
	}
	
	protected function escapeString($str) {
		return htmlspecialchars($str, ENT_QUOTES, "UTF-8");
	}
	
	public function e($name) {
		echo $this->escapeString($this->getVar($name));
	}

	public function t($name) {
		
	}
	
	public static function errorHandler($errno, $errstr, $errfile, $errline, $errcontext) {
		if($errno == 8) {
			$name = str_replace('Undefined variable: ','',$errstr);
			echo '{{' . $name . '}}';
			return true;
		}
		echo $errstr;
	}
	
	protected function escapeVars() {
		foreach($this->vars as $k => $v) {
			$ev = $v;
			if(is_string($v)) {
				$ev = $this->escapeString($v);
			}
			$this->escapedVars[$k] = $ev;
		}
	}

	/**
	 * Render the template
	 * @return string
	 */
	public function render() {
		$this->escapeVars();
		extract(array_merge($this->escapedVars), EXTR_REFS);

//		set_error_handler(array(__CLASS__, 'errorHandler'));

		ob_start();
		include($this->filename);
		$output = ob_get_clean();

		restore_error_handler();

		return $output;
	}

	/**
	 * Implement toString
	 * @return string
	 */
	public function __toString() {
		try {
			return $this->render();
		} catch (\Exception $e) {
			return $e->getMessage();
		}
	}

	/**
	 * Helpers functions inside the view
	 * 
	 * $this->doSomething()
	 * 
	 * @param string $name
	 * @param array $arguments
	 */
	public function callHelper($name, $arguments) {
		if (!isset($this->helpers[$name])) {
			$parent = $this->getParent();
			if($parent) {
				return $parent->callHelper($name, $arguments);
			}
			throw new BadMethodCallException("Helpers '$name' does not exist");
		}
		$helper = $this->helpers[$name];
		return call_user_func_array($helper, $arguments);
	}
	
	public function __get($name) {
		return $this->getVar($name);
	}
	
	public function __set($name, $value) {
		$this->setVar($name, $value);
	}

	public function __call($name, $arguments) {
		return $this->callHelper($name, $arguments);
	}

}