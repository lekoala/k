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

	/* view helpers */

	public function notifications() {
		$notifications = $this->getSession()->take('notifications', array());
		$arr = array();
		foreach ($notifications as $options) {
			$this->getApp()->getLogger()->debug($options['text']);
			$arr[] = json_encode($options);
		}
		$js = '';
		if (!empty($arr)) {
			$js = '<script>var notifications = [' . implode(",\n", $arr) . '];</script>';
		}
		return $js;
	}

	public function viewModel($item, $tpl = 'record') {
		if (!$item instanceof Model) {
			return 'Not a model';
		}
		$filename = 'models/' . $item::getTableName() . '/' . $tpl;
		$v = $this->getApp()->createView($filename);
		if (!$v) {
			return;
		}
		$v->setVar('o', $item);
		return $v;
	}

	public function devToolbar() {
		if (!$this->config('debug')) {
			return;
		}
		$app = $this->getApp();
		$o = new DevToolbar();
		$o->track($app);
		$o->track($app->getProfiler());
		$o->track($app->getDb());
		$o->track($app->getLogger(), function($o, $tb) {
			$log = $o->getLogs();
			$arr = array();
			foreach ($log as $l) {
				$arr[] = '[' . $l['level'] . "] \t" . $l['message'];
			}
			array_unshift($arr, count($arr) . ' logs');
			return $arr;
		});
		return $o;
	}

	public function isLocal() {
		return $this->getRequest()->isLocal();
	}

}