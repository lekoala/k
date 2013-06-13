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

	public function get($name, $type = 'string') {
		switch ($type) {
			case 'object':
				return new stdClass();
			case 'array':
				return [];
			case 'string':
				return '{{' . $name . '}}';
		}
	}

	public function getVar($name) {
		if (array_key_exists($name, $this->vars)) {
			return $this->vars[$name];
		}
	}

	public function getVars() {
		return $this->vars;
	}

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

	public function setVar($k, $v) {
		if (!array_key_exists($k, $this->vars)) {
			$this->vars[$k] = '';
		}
		$this->vars[$k] = $v;
		return $this;
	}
	
	public function addVars($vars) {
		$this->setVars($vars, false);
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
		//TODO: translate
	}

	public static function errorHandler($errno, $errstr, $errfile, $errline, $errcontext) {
		if ($errno == 8) {
			$name = str_replace('Undefined variable: ', '', $errstr);
			echo '{{' . $name . '}}';
			return true;
		}
		echo $errstr;
	}

	protected function escapeVars() {
		foreach ($this->vars as $k => $v) {
			$ev = $v;
			if (is_string($v)) {
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