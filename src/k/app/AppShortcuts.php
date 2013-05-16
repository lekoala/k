<?php

namespace k\app;

/**
 * Provide access to app related functionnality for classes where
 * the app is injected
 *
 * @author lekoala
 */
trait AppShortcuts {

	/**
	 * Store the app instance
	 * 
	 * @var App
	 */
	protected $app;

	public function getApp() {
		return $this->app;
	}

	public function setApp($app) {
		if (!$app instanceof \k\app\App) {
			throw new InvalidArgumentException('You must pass an instance of k\app, ' . get_class($app) . ' was passed');
		}
		$this->app = $app;
		return $this;
	}
	
	public function getService($name) {
		return $this->getApp()->getService($name);
	}

	/* shortcut to access app properties */

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
}