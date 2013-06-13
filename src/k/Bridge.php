<?php

namespace k;

/**
 * Provide access to app related functionnality for classes where
 * the app is injected
 *
 * @author lekoala
 */
trait Bridge {

	/**
	 * Get current app instance
	 * 
	 * @return App
	 */
	public function getApp() {
		return App::getInstance();
	}

	/**
	 * @return Service
	 */
	public function getService($name) {
		return $this->getApp()->createService($name);
	}

	/**
	 * @return View
	 */
	public function getLayout() {
		return $this->getApp()->getLayout();
	}

	/**
	 * @return View
	 */
	public function getView() {
		return $this->getApp()->getView();
	}
	
	/**
	 * @return Module
	 */
	public function getModule() {
		return $this->getApp()->getModule();
	}

	/**
	 * @return \k\db\Pdo
	 */
	public function getDb() {
		return $this->getApp()->getDb();
	}

	/**
	 * @return \k\session\Session
	 */
	public function getSession() {
		return $this->getApp()->getSession();
	}
	
	/**
	 * @return Response
	 */
	public function getResponse() {
		return $this->getApp()->getResponse();
	}
	
	/**
	 * @return Request
	 */
	public function getRequest() {
		return $this->getApp()->getRequest();
	}
	
	/**
	 * Utility function to run code that can trigger exception and convert
	 * them as error notification
	 * 
	 * @param function $callback
	 * @return mixed
	 */
	public function notify($text, $options = array()) {
		if (!is_array($options)) {
			$options = array('type' => $options);
		}
		if (is_array($text)) {
			$options = $text;
		}
		$options['text'] = $text;
		$options['history'] = false;
		if ($this->getRequest()->accept('application/json')) {
			return $this->getResponse()->addData('notifications', $options);
		}
		return $this->getSession()->add('notifications', $options);
	}
	
	public function deny($message = 'Forbidden') {
		throw new AppException($message);
	}
	
	/**
	 * @return Image
	 */
	public function getFile($file) {
		$ext = strtolower(pathinfo($file,PATHINFO_EXTENSION));
		if(in_array($ext, array('jpg','jpeg','png','gif'))) {
			return new Image($file);
		}
		else {
			return new File($file);
		}
	}
	
	/**
	 * @return \k\Directory
	 */
	public function getDir($dir) {
		return new Directory($dir);
	}
	
	/**
	 * Get a value from the config
	 * 
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function config($key, $default = null) {
		$conf = $this->getApp()->getConfig();
		$loc = &$conf;
		foreach (explode('/', $key) as $step) {
			if (isset($loc[$step])) {
				$loc = &$loc[$step];
			} else {
				return $default;
			}
		}
		return $loc;
	}

	
	/**
	 * Get/set a value from the session
	 * 
	 * @param string $k
	 * @param mixed $v
	 * @return mixed
	 */
	public function session($k, $v = null) {
		if ($v === null) {
			return $this->getApp()->getSession()->get($k);
		}
		return $this->getApp()->getSession()->set($k, $v);
	}
}