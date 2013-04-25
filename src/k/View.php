<?php

namespace k;

use \InvalidArgumentException;
use \BadMethodCallException;

/**
 * Simple view wrapper
 */
class View {

	/**
	 * Path to the view to render
	 * 
	 * @var string
	 */
	protected $filename;

	/**
	 * 
	 * Variables that will be made available to the view
	 * 
	 * @var array
	 */
	protected $vars;

	/**
	 * Store view helpers in an array
	 * @var array
	 */
	protected $helpers = array();
	
	/**
	 * If view is embedded in a view
	 * @var View
	 */
	protected $parent;

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
				$view->setParent($this);
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
		if(isset($this->vars[$name])) {
			return $this->vars[$name];
		}
		return 'Undefined variable : ' . $name;
	}

	public function getVars() {
		return $this->vars;
	}

	public function setVars($vars) {
		if (!is_array($vars)) {
			$vars = array('content' => $vars);
		}
		$this->vars = $vars;
		return $this;
	}

	public function setVar($k, $v) {
		$this->vars[$k] = $v;
		return $this;
	}

	public function addVars($vars) {
		if (!is_array($vars)) {
			$vars = array('content' => $vars);
		}
		$this->vars = array_merge($this->vars, $vars);
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

	public function e($name) {
		echo htmlspecialchars($this->getVar($name), ENT_QUOTES, "UTF-8");
	}

	public function t($name) {
		
	}

	public static function errorHandler($errno, $errstr, $errfile, $errline, $errcontext) {
		echo $errstr;
	}

	/**
	 * Render the template
	 * @return string
	 */
	public function render() {
		extract(array_merge($this->vars), EXTR_REFS);

		set_error_handler(array(__CLASS__, 'errorHandler'));

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
			$parent = $this->parent;
			if($parent) {
				return $parent->callHelper($name, $arguments);
			}
			throw new BadMethodCallException("Helpers '$name' does not exist");
		}
		$helper = $this->helpers[$name];
		return call_user_func_array($helper, $arguments);
	}

	public function __call($name, $arguments) {
		return $this->callHelper($name, $arguments);
	}

}