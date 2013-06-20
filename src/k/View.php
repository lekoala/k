<?php

namespace k;

use \InvalidArgumentException;
use \BadMethodCallException;
use \stdClass;

/**
 * Simple view wrapper
 */
class View {
	use Bridge;

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
	 * Registered helpers
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

	/**
	 * Get view filename
	 * 
	 * @return string
	 */
	public function getFilename() {
		return $this->filename;
	}

	/**
	 * Set view filename
	 * 
	 * @param string $filename
	 * @return \k\View
	 * @throws InvalidArgumentException
	 */
	public function setFilename($filename) {
		if (!is_file($filename)) {
			throw new InvalidArgumentException($filename . ' does not exist');
		}
		$this->filename = $filename;
		return $this;
	}

	/**
	 * Get a typed var
	 * 
	 * @param string $name
	 * @param string $type string,array,object
	 * @return \stdClass
	 */
	public function get($name, $type = 'string') {
		$var = $this->getEscapedVar($name);
		
		switch ($type) {
			case 'object':
				if(!is_object($var)) {
					return new stdClass();
				}
			case 'array':
				if(!is_array($var)) {
					return [];
				}
			case 'string':
				if(!is_string($var)) {
					return '{{' . $name . '}}';
				}
		}
		
		return $var;
	}
	
	/**
	 * Create a menu
	 * 
	 * @return \k\html\Menu
	 */
	public function createMenu() {
		return new \k\html\Menu();
	}
	
	/**
	 * Create a table
	 * 
	 * @return \k\html\Table
	 */
	public function createTable() {
		return new \k\html\Table();
	}
	
	/**
	 * Create a form
	 * 
	 * @return \k\html\Form
	 */
	public function createForm() {
		return new \k\html\Form();
	}

	/**
	 * Get a var from the view
	 * 
	 * @param string $name
	 * @return mixed
	 */
	public function getVar($name) {
		if (array_key_exists($name, $this->vars)) {
			return $this->vars[$name];
		}
	}
	
	/**
	 * Get an escape var
	 * 
	 * @param string $name
	 * @return mixed
	 */
	public function getEscapedVar($name) {
		return $this->escape($this->getVar($name));
	}

	/**
	 * Get all vars
	 * 
	 * @return array
	 */
	public function getVars() {
		return $this->vars;
	}

	/**
	 * Set all vars
	 * 
	 * @param array $vars
	 * @param bool $clean Reset existing vars?
	 * @return \k\View
	 */
	public function setVars($vars, $clean = true) {
		if (!is_array($vars)) {
			$vars = array('content' => $vars);
		}
		if ($clean) {
			$this->vars = array();
		}
		foreach ($vars as $k => $v) {
			$this->setVar($k, $v);
		}
		return $this;
	}

	/**
	 * Set a variable
	 * 
	 * @param string $k
	 * @param mixed $v
	 * @return \k\View
	 */
	public function setVar($k, $v) {
		if (!array_key_exists($k, $this->vars)) {
			$this->vars[$k] = '';
		}
		$this->vars[$k] = $v;
		return $this;
	}
	
	/**
	 * Alias setVars($vars,false)
	 * 
	 * @param array $vars
	 * @return \k\View
	 */
	public function addVars($vars) {
		$this->setVars($vars, false);
		return $this;
	}

	/**
	 * Get registered helpers
	 * 
	 * @return array
	 */
	public function getHelpers() {
		return $this->helpers;
	}

	/**
	 * Set helpers
	 * 
	 * @param array $helpers
	 * @return \k\View
	 */
	public function setHelpers($helpers) {
		$this->helpers = $helpers;
		return $this;
	}

	/**
	 * Add an helper
	 * 
	 * @param string $name
	 * @param array|callable $helper
	 * @return \k\View
	 */
	public function addHelper($name, $helper) {
		$this->helpers[$name] = $helper;
		return $this;
	}

	/**
	 * Get parent view
	 * 
	 * @return \k\View
	 */
	public function getParent() {
		return $this->parent;
	}

	/**
	 * Set parent view
	 * 
	 * @param \k\View $parent
	 * @return \k\View
	 */
	public function setParent(View $parent) {
		$this->parent = $parent;
		$this->parent->content = $this;
		return $this;
	}
	
	/**
	 * Escape a variable
	 * 
	 * @param mixed $str
	 * @return mixed
	 */
	protected function escape($str) {
		//TODO: wrap array in 
		if(is_string($str)) {
			return htmlspecialchars($str, ENT_QUOTES, "UTF-8");
		}
	}

	/**
	 * Shortcut to echo escaped vars in the view
	 * @param string $name
	 */
	public function e($name) {
		echo $this->getEscapedVar($name);
	}

	public function t($name) {
		//TODO: translate
	}

	/**
	 * Fail nicely in view
	 * 
	 * @param type $errno
	 * @param type $errstr
	 * @param type $errfile
	 * @param type $errline
	 * @param type $errcontext
	 * @return boolean
	 */
	public static function errorHandler($errno, $errstr, $errfile, $errline, $errcontext) {
		if ($errno == 8) {
			$name = str_replace('Undefined variable: ', '', $errstr);
			echo '{{' . $name . '}}';
			return true;
		}
		echo $errstr;
	}

	/**
	 * Escape all vars
	 */
	protected function escapeVars() {
		foreach ($this->vars as $k => $v) {
			$ev = $v;
			if (is_string($v)) {
				$ev = $this->getEscapedVar($v);
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

		if(!$this->config('debug')) {
			set_error_handler(array(__CLASS__, 'errorHandler'));
		}
		
		ob_start();
		include($this->filename);
		$output = ob_get_clean();

		if(!$this->config('debug')) {
			restore_error_handler();
		}

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
	 * Helpers functions inside the view. Helpers are registered in the controller
	 * or defined through addHelper
	 * 
	 * $this->doSomething()
	 * 
	 * @param string $name
	 * @param array $arguments
	 */
	public function callHelper($name, $arguments) {
		$helper = null;
		if (isset($this->helpers[$name])) {
			$helper = $this->helpers[$name];
		} else {
			$app = $this->getApp();
			$controller = $app->getController();
			if (method_exists($controller, $name)) {
				$helper = array($controller, $name);
			}
			elseif(method_exists($app, $name)) {
				$helper = array($app, $name);
			}
		}
		if (!$helper) {
			$parent = $this->getParent();
			if ($parent) {
				return $parent->callHelper($name, $arguments);
			}
			throw new BadMethodCallException("Helpers '$name' does not exist");
		}
		$res = call_user_func_array($helper, $arguments);
		return $res;
	}

	public function __get($name) {
		return $this->get($name);
	}

	public function __set($name, $value) {
		$this->setVar($name, $value);
	}

	public function __call($name, $arguments) {
		return $this->callHelper($name, $arguments);
	}

}