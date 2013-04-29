<?php

namespace k;

use \BadMethodCallException;

/**
 * ViewRenderer
 *
 * @author lekoala
 */
class ViewRenderer {

	protected $baseDir;
	protected $defaultView = 'index';
	protected $defaultExtension = 'phtml';
	protected $layout;
	protected $viewFolder = 'views';
	protected $helpers = array();

	public function __construct() {
		
	}

	public function getBaseDir() {
		return $this->baseDir;
	}

	public function setBaseDir($baseDir) {
		$this->baseDir = $baseDir;
		return $this;
	}

	public function getDefaultView() {
		return $this->defaultView;
	}

	public function setDefaultView($defaultView) {
		$this->defaultView = $defaultView;
		return $this;
	}

	public function getDefaultExtension() {
		return $this->defaultExtension;
	}

	public function setDefaultExtension($defaultExtension) {
		$this->defaultExtension = $defaultExtension;
		return $this;
	}

	public function getLayout() {
		return $this->layout;
	}

	public function setLayout($layout) {
		$this->layout = $layout;
		return $this;
	}

	public function getViewFolder() {
		return $this->viewFolder;
	}

	public function setViewFolder($viewFolder) {
		$this->viewFolder = $viewFolder;
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

	public function getViewDir() {
		return $this->getBaseDir() . '/' . $this->getViewFolder();
	}

	public function resolve($filename, $dir = null) {
		if (empty($filename)) {
			return false;
		}
		if (strpos($filename, '/') === 0 && is_file($filename)) {
			return $filename;
		}

		//append extension if needed
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		if (empty($ext)) {
			$filename .= '.' . $this->getDefaultExtension();
		}

		//if we don't have a dir, use the base one
		if ($dir === null) {
			$dir = $this->baseDir;
		}
		//if the view dir is not appended, add it
		if (strpos($dir, '/' . $this->getViewFolder()) === false) {
			$dir .= '/' . $this->getViewFolder();
		}
		//if the file don't start with /, prepend dir
		if (strpos($filename, '/') !== 0) {
			$filename = $dir . '/' . $filename;
		}
		if (is_file($filename)) {
			return $filename;
		}
		return false;
	}

	public function render($view, $vars = array()) {
		if (is_string($view)) {
			$view = new View($view, $vars);
		} else {
			$view->addVars($vars);
		}

		// Extract variables as references
		extract(array_merge($view->getVars()), EXTR_REFS);

		//cleanup scope
		unset($vars);

		ob_start();
		include($view->getFilename());
		return ob_get_clean();
	}

	public function renderWithLayout($view) {
		return $this->render(array($view, $this->getLayout()));
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
			throw new BadMethodCallException("Helpers '$name' does not exist");
		}
		$helper = $this->helpers[$name];
		return call_user_func_array($helper, $arguments);
	}

	public function __call($name, $arguments) {
		return $this->callHelper($name, $arguments);
	}

}