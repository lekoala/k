<?php

namespace k;

use \InvalidArgumentException;

/**
 * The controller handles extra logic to handle actions triggered in the view
 * 
 * Business logic should be forwared to the models as much as possible
 */
class Controller {

	/**
	 * A controller needs to know about the app that is the central hub
	 * and class factory
	 * 
	 * @var App
	 */
	protected $app;

	/**
	 * Set to true or false to activate global auth or use an array that looks
	 * like
	 * [
	 * 'method',
	 * 'adminMethod' => 'admin'
	 * ]
	 * 
	 * @var bool|array
	 */
	protected $requiresAuth;

	/**
	 * Resolved name of the controller
	 * 
	 * @var string
	 */
	protected $name;

	/**
	 * The view for this controller/method if the template exists
	 * 
	 * @var View
	 */
	protected $view;

	/**
	 * Layout of the app, if any
	 * 
	 * @var View 
	 */
	protected $layout;

	public function __construct($app) {
		if (!$app instanceof \k\App) {
			throw new InvalidArgumentException('You must pass an instance of k\app, ' . get_class($app) . ' was passed');
		}
		$this->app = $app;
		$this->init();
	}

	public function pre() {
		//implement in subclass
	}

	public function post() {
		//implement in subclass
	}

	public function init() {
		//implement in subclass
	}

	public function e($callback) {
		try {
			return $callback();
		} catch (\Exception $e) {
			$this->getApp()->notify($e->getMessage(), 'error');
		}
		return false;
	}

	/**
	 * Look for parameter in get or post
	 * 
	 * Any value will be automatically added to the view if it exists
	 * 
	 * @param string $k
	 * @param int|array $filter
	 * @param mixed $default
	 * @return mixed
	 */
	public function in($k, $filter = null, $default = null) {
		$v = $this->getApp()->getHttp()->in($k, $default);
		if ($this->getView()) {
			$this->getView()->$k = $v;
		}
		return $v;
	}

	public function isGet() {
		return $this->getApp()->getHttp()->isGet();
	}

	public function isPost() {
		return $this->getApp()->getHttp()->isPost();
	}

	public function isUpdate() {
		return $this->getApp()->getHttp()->isUpdate();
	}

	public function isDelete() {
		return $this->getApp()->getHttp()->isDelete();
	}

	public function redirect($url) {
		if (strpos($url, '.') === 0) {
			$name = str_replace($this->getApp()->getControllerPrefix(), '', $this->getName());
			$name = trim(strtolower(preg_replace('/([A-Z])/', "_$1", $name)), '_');
			$url = str_replace('./', '/' . $name . '/', $url);
		}
		$this->getApp()->getHttp()->redirect($url, 302, true);
	}

	public function redirectBack() {
		$this->getApp()->getHttp()->redirectBack();
	}

	public function getApp() {
		return $this->app;
	}

	public function setApp($app) {
		$this->app = $app;
		return $this;
	}

	public function getRequiresAuth() {
		return $this->requiresAuth;
	}

	public function setRequiresAuth($requiresAuth) {
		$this->requiresAuth = $requiresAuth;
		return $this;
	}

	public function getName() {
		if ($this->name === null) {
			$obj = explode('\\', get_called_class());
			$this->name = end($obj);
		}
		return $this->name;
	}
	
	public function getService($name) {
		return $this->getApp()->resolveService($name);
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
	
	public function getUser() {
		return $this->getApp()->getUser();
	}

	public function getSession() {
		return $this->getApp()->getSession();
	}

	public function session($k, $v = null) {
		if ($v === null) {
			return $this->getSession()->get($k);
		}
		return $this->getSession()->set($k, $v);
	}

}