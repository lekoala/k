<?php

namespace k;

/**
 * Provide access to app related functionnality for classes where
 * the app is injected
 *
 * @author lekoala
 */
trait Bridge {

	public function getApp() {
		return App::getInstance();
	}

	public function getService($name) {
		return $this->getApp()->createService($name);
	}

	public function getLayout() {
		return $this->getApp()->getLayout();
	}

	public function getView() {
		return $this->getApp()->getView();
	}

	public function getDb() {
		return $this->getApp()->getDb();
	}

	public function getSession() {
		return $this->getApp()->getSession();
	}
	
	public function getResponse() {
		return $this->getApp()->getResponse();
	}
	
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