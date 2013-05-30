<?php

namespace k\app;

use \InvalidArgumentException;
use \RuntimeException;

/**
 * The controller handles extra logic to handle actions triggered in the view
 * 
 * Business logic should be forwared to the models as much as possible
 */
class Controller {

	use AppShortcuts;

use SyntaxHelpers;

	protected $name;

	/**
	 * Define actions
	 * 
	 * @var array
	 */
	protected $actions;

	public function __construct($app) {
		$this->setApp($app);
		$this->init();
	}

	public function pre($method, $params) {
		//implement in subclass
	}

	public function post($method, $params) {
		//implement in subclass
	}

	public function init() {
		//implement in subclass
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
		$this->getApp()->getResponse()->redirect($url,302,true);
	}

	public function redirectBack() {
		$this->getApp()->getResponse()->redirectBack();
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

	public function __call($name, $arguments) {
		//look for an action
		if ($this->actions) {
			$actionName = ucfirst($name);
			$actions = array_keys($this->actions);
			if (in_array($actionName, $actions)) {
				$class = $actionName;
				if (isset($this->actions[$actionName])) {
					$class = $this->actions[$actionName];
				}
				if (!class_exists($class)) {
					//look in framework
					$class = '\\k\\app\\action\\' . $class;
					if (!class_exists($class)) {
						throw new RuntimeException("Action '$actionName' does not exist");
					}
				}
				$o = new $class($this);
				$r = call_user_func_array(array($o, 'run'), $arguments);
				return $r;
			}
		}
	}

}