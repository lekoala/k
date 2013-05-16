<?php

namespace k\app;

/**
 * Avoid too verbose syntax
 *
 * @author lekoala
 */
trait SyntaxHelpers {

	/**
	 * Get/set a value from the session
	 * 
	 * @param string $k
	 * @param mixed $v
	 * @return mixed
	 */
	public function session($k, $v = null) {
		if ($v === null) {
			return $this->getSession()->get($k);
		}
		return $this->getSession()->set($k, $v);
	}

	/**
	 * Get a value from the config
	 * 
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function config($key, $default = null) {
		$loc = &$this->config;
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