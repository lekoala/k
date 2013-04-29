<?php

namespace k;

use \Exception;
use \RuntimeException;
use \InvalidArgumentException;

class Core {

	/**
	 * Base dir for app files
	 * @var fs\Dir
	 */
	protected $dir;

	/**
	 * A config loaded, in dir/config.php
	 * @var Config
	 */
	protected $config;

	/**
	 * Http helper
	 * @var Http
	 */
	protected $http;

	/**
	 * A db if configured in config
	 * @var db\Pdo
	 */
	protected $db;

	/**
	 * Auth provider if needed
	 * @var Auth
	 */
	protected $auth;

	/**
	 * Session storage
	 * @var Session
	 */
	protected $session;

	/**
	 * A layout to render views
	 * @var View
	 */
	protected $layout;

	/**
	 * Renders
	 * @var View
	 */
	protected $view;
	protected $user;
	protected $userToken = 'user_id';
	protected $userService = 'person';
	protected $debugBar;
	protected $viewDir = 'views';
	protected $viewExtension = 'phtml';
	protected $modules = array();
	protected $module;
	protected $moduleName;
	protected $defaultModule;
	protected $defaultController = 'home';
	protected $action;
	protected $actionName;
	protected $defaultAction = 'index';
	protected $configFile = 'config.php';
	protected $controller;
	protected $controllerName;
	protected $controllerPrefix = 'Controller_';
	protected $modulePrefix = 'Module_';
	protected $moduleClass = '\k\Module';
	protected $servicePrefix = 'Service_';
	protected $services = array();
	protected $genericController;

	/**
	 * Create a new app
	 * 
	 * Pass the dir where the app lives
	 * 
	 * @param string $dir If omitted, will use the parent folder
	 * @throws InvalidArgumentException
	 */
	public function __construct($dir = null) {
		if ($dir === null) {
			$dir = realpath(__DIR__ . '/../');
		}
		$this->setDir($dir, false);

		$configFile = $dir . '/' . $this->configFile;
		$this->setConfig($configFile);

		if ($this->config->get('db')) {
			$this->db = new db\Pdo($this->config->get('db'));
		}
		if ($this->config->get('auth')) {
			$this->auth = new Auth($this->config->get('auth'));
		}

		$this->session = new Session();
		$this->http = new Http();
		$this->viewRenderer = new ViewRenderer();
		
		$this->init();
	}
	
	public function init() {
		//implement in subclass
	}

	public function registerViewHelpers($r) {
		//implement in subclass and add to view renderer
	}

	/**
	 * Return the base dir where the app lives
	 * 
	 * @return string|fs\Directory
	 */
	public function getDir($asObject = false) {
		$this->dir->rewind();
		if (!$asObject) {
			return (string) $this->dir->getPath();
		}
		return $this->dir;
	}

	public function setDir($dir, $check = true) {
		if ($check && !is_dir($dir)) {
			throw new InvalidArgumentException("$dir is not a directory");
		}
		if (is_string($dir)) {
			$dir = new fs\Directory($dir);
		}
		$this->dir = $dir;
	}

	/**
	 * @return Config
	 */
	public function getConfig() {
		return $this->config;
	}

	/**
	 * Set a config for this application
	 * 
	 * @param string $file
	 * @return \k\App
	 */
	public function setConfig($file) {
		if (is_file((string) $file)) {
			$this->config = new Config($file);
		} else {
			$this->config = new Config();
		}
		return $this;
	}

	/**
	 * @return Http
	 */
	public function getHttp() {
		return $this->http;
	}

	/**
	 * @return \k\db\Pdo
	 */
	public function getDb() {
		return $this->db;
	}

	/**
	 * @return ViewRenderer
	 */
	public function getViewRenderer() {
		return $this->viewRenderer;
	}

	/**
	 * @return Layout
	 */
	public function getLayout($view = null) {
		if ($this->layout === null) {
			$layoutName = $this->getConfig()->get('view/layout', 'layout/default');
			$this->layout = $this->createView($layoutName);
		}
		if ($view) {
			$this->layout->setVar('content', $view);
			$view->setParent($this->layout);
		}
		return $this->layout;
	}

	/**
	 * @return Session
	 */
	public function getSession() {
		return $this->session;
	}

	public function getServicePrefix() {
		return $this->servicePrefix;
	}

	public function getControllerPrefix() {
		return $this->controllerPrefix;
	}

	public function getAction() {
		return $this->action;
	}

	public function getController($asObject = true) {
		if (!$asObject) {
			return (string) $this->controller;
		}
		return $this->controller;
	}

	public function getModuleName() {
		return $this->moduleName;
	}

	public function getActionName() {
		return $this->actionName;
	}

	public function getControllerName() {
		return $this->controllerName;
	}
	
	public function getView() {
		return $this->view;
	}

	protected function initModules() {
		$modules = $this->config->get('modules', array());
		$this->defaultModule = $this->config->get('default_module', 'main');
		if (!is_string($this->defaultModule)) {
			throw new InvalidArgumentException('Default module should be a string');
		}

		//build module list by reading directory
		if (empty($modules)) {
			$dir = new fs\Directory($this->dir . '/src/modules');
			foreach ($dir as $fi) {
				if ($fi->isFile()) {
					$modules[] = strtolower($fi->getBasename('.php'));
				}
			}
		}

		if (!in_array($this->defaultModule, $modules)) {
			throw new RuntimeException('Default module does not exist : ' . (string) $this->defaultModule);
		}

		foreach ($modules as $module) {
			$this->modules[$module] = new Module($this);
		}
	}

	/**
	 * Get a module
	 * 
	 * @param string $name
	 * @return Module
	 * @throws RuntimeException
	 */
	protected function getModule($name) {
		if (!isset($this->modules[$name])) {
			throw new RuntimeException("Module '$name' does not exists");
		}
		return $this->modules[$name];
	}

	public function run() {
		$result = $this->handle($this->http->getPath(false));

		$this->http->header('Content-Type: text/html; charset=utf-8');
		echo $result;
	}

	public function notify($text, $options = array()) {
		if (!is_array($options)) {
			$options = array('type' => $options);
		}
		if (is_array($text)) {
			$options = $text;
		}
		$options['text'] = $text;
		$options['history'] = false;
		return $this->getSession()->add('notifications', $options);
	}

	public function handle($path) {
		$path = trim($path, '/');
		$parts = explode('/', $path);
		$parts = array_filter($parts);

		//match to a module if we are using them
		$moduleName = null;
		if (is_dir($this->dir . '/src/modules')) {
			$moduleName = $this->defaultModule;
			$modules = array_keys($this->modules);
			if (!empty($parts[0]) && in_array($moduleName, $modules)) {
				$moduleName = array_shift($parts);
			}
			$this->moduleName = $moduleName;
			$this->module = $this->createModule($moduleName);
		}

		$controllerName = $this->defaultController;
		if (!empty($parts)) {
			$controllerName = array_shift($parts);
		}
		$this->controllerName = $controllerName;
		$this->controller = $this->createController($controllerName, $this->module);

		$actionName = $this->defaultAction;
		if (!empty($parts)) {
			$actionName = array_shift($parts);
		}
		$this->actionName = $actionName;

		//if we have an ajax request requesting json, don't bother to create a view
		if (!($this->getHttp()->isAjax() && $this->getHttp()->accept('application/json'))) {
			$this->view = $this->createView($controllerName, $actionName, $moduleName); //this will be passed to the controller
			//wrap in layout
			$this->layout = $this->getLayout($this->view);
		}

		$response = array();
		if ($this->controller) {
			//indexAction...
			$method = lcfirst(str_replace(' ', '', ucwords(preg_replace('/[^A-Z^a-z^0-9]+/', ' ', $actionName))))
			  . 'Action';
			$this->action = $method;

			$preResponse = $this->callAction($this->controller, 'pre');
			$methodResponse = $this->callAction($this->controller, $method, $parts);
			$postResponse = $this->callAction($this->controller, 'post');

			$response = array_merge($preResponse, $methodResponse, $postResponse);
		}

		//render with template
		if ($this->view) {
			$this->view->addVars($response);
			if ($this->layout) {
				$this->registerViewHelpers($this->layout);
				return $this->layout->render();
			}
			$this->registerViewHelpers($this->view);
			return $this->view->render();
		}
	}

	protected function callAction(Controller $controller, $name, $parts = array()) {
		$r = array();
		if (method_exists($controller, $name)) {
			$r = call_user_func_array(array($controller, $name), $parts);
		}
		if (!is_array($r)) {
			$r = array('response' => $r);
		}
		return $r;
	}

	protected function createView($controller, $action = null, $module = null) {
		$view = null;
		$name = $controller;
		if (is_array($name)) {
			$name = implode('/', $name);
		}
		if ($action) {
			$name = $controller . '/' . $action;
		}
		$filename = $this->getDir() . '/' . $this->viewDir;
		if ($module) {
			$filename .= '/' . $module;
		}
		$filename .= '/' . $name;
		$filename .= '.' . $this->viewExtension;
		if (is_file($filename)) {
			$view = new View($filename);
		}
		return $view;
	}

	protected function createController($name, $module = null) {
		$class = '';
		if ($this->controllerPrefix) {
			$class .= $this->controllerPrefix;
		}
		if ($module) {
			$class .= $this->modulePrefix;
		}
		$class .= str_replace(' ', '', ucwords(preg_replace('/[^A-Z^a-z^0-9]+/', ' ', $name)));
		if (!class_exists($class) && $this->genericController) {
			$class = $this->genericController;
		}
		if (!class_exists($class)) {
			return null;
		}
		$controller = new $class($this);
		return $controller;
	}

	public function resolveService($name) {
		$class = $this->servicePrefix . str_replace(' ', '', ucwords(preg_replace('/[^A-Z^a-z^0-9]+/', ' ', $name)));
		if (!class_exists($class)) {
			throw new RuntimeException("Service '$name' does not exist");
		}
		if (!isset($this->services[$name])) {
			$o = new $class($this);
			if (!$o instanceof service\Service) {
				throw new RuntimeException("Service $name must extend base service class");
			}
			$this->services[$name] = $o;
		}
		return $this->services[$name];
	}

	public function __toString() {
		try {
			return (string) $this->run();
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}

}