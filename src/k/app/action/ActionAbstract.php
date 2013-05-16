<?php

namespace k\app\action;

/**
 * A reusable piece of action
 *
 * @author lekoala
 */
class ActionAbstract {

	protected $controller;

	public function __construct($controller) {
		$this->setController($controller);
	}

	/**
	 * @return \k\app\Controller
	 */
	public function getController() {
		return $this->controller;
	}

	/**
	 * @param \k\app\Controller $controller
	 * @return \k\app\action\ActionAbstract
	 */
	public function setController($controller) {
		$this->controller = $controller;
		return $this;
	}

	/**
	 * @return \k\app\App
	 */
	public function getApp() {
		return $this->getController()->getApp();
	}

	public function getBaseDir() {
		return $this->getApp()->getFrameworkDir('action');
	}

	public function getViewDir() {
		return $this->getApp()->getFrameworkDir('views/action');
	}

	public function streamResponse() {
		@apache_setenv('no-gzip', 1);
		@ini_set('zlib.output_compression', 0);
		@ini_set('implicit_flush', 1);
		for ($i = 0; $i < ob_get_level(); $i++) {
//			ob_end_flush();
		}
//		ob_implicit_flush(1);
	}

}