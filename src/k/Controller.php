<?php

namespace k;

use \InvalidArgumentException;
use \RuntimeException;

/**
 * The controller handles extra logic to handle actions triggered in the view
 * 
 * Business logic should be forwared to the models/services as much as possible
 */
abstract class Controller {
	use Bridge;

	protected $name;

	/**
	 * Define actions
	 * 
	 * @var array
	 */
	protected $actions;

	public function pre($method, $params) {
		//implement in subclass
	}

	public function post($method, $params) {
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
		$v = $this->getRequest()->in($k, $default);
		if ($this->getView()) {
			$this->getView()->$k = $v;
		}
		return $v;
	}

	public function method($v = null) {
		return $this->getRequest()->method($v);
	}
	
	protected function file($filename, $name = null, $force = false) {
		if (!is_file($filename)) {
			//TODO
		}
		if (!$name) {
			$name = basename($filename);
		}
		$filesize = filesize($filename);
		//large files should be handled by apache directly
		if ($filesize > $this->getApp()->config('large_file_limit')) {
			$location = '/files/' . $name;
			$symbolic = $this->getApp()->getPublicDir() . $location;
			$res = system(sprintf('ln -s %s %s', $filename, $symbolic), $ret);
			if ($res !== false) {
				$this->redirect($location);
			}
		} else {
			$this->getResponse()->file($filename, $name, $force);
		}
	}

	protected function redirect($url) {
		if (strpos($url, '.') === 0) {
			$name = $this->getApp()->getUrlSegment();
			$url = preg_replace('#^\./#', $name . '/', $url);
		}
		$this->getApp()->getResponse()->redirect($url, 302, true);
	}
	
	protected function line($v) {
		if(php_sapi_name() == 'cli') {
			echo "$v\n";
		}
		else {
			$view = $this->getView();
			if(!$view) {
				//create a generic view to render content
				$view = $this->getApp()->createView('utils/blank');
				$this->getApp()->setView($view);
			}
			$c = $view->getVar('content');
			$view->content = $c . $v . '<br/>'; 
		}
	}

	protected function redirectBack() {
		$this->getApp()->getResponse()->redirectBack();
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