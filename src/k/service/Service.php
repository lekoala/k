<?php

namespace k\service;

use \InvalidArgumentException;

/**
 * Service
 *
 * @author lekoala
 */
class Service {
	
	protected $app;

	public function __construct($app) {
		$this->setApp($app);
	}
	
	/**
	 * Get attached app
	 * 
	 * @return \k\App
	 */
	public function getApp() {
		return $this->app;
	}

	/**
	 * Set attached app
	 * 
	 * @param \k\App $app
	 * @return \k\Service
	 * @throws InvalidArgumentException
	 */
	public function setApp($app) {
		if(! $app instanceof \k\App) {
			throw new InvalidArgumentException("App must be an instance of app");
		}
		$this->app = $app;
		return $this;
	}

}