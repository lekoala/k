<?php

namespace k\app;

use \Exception;
use \RuntimeException;
use \InvalidArgumentException;
use \ReflectionClass;

class App {

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
	 * Session storage
	 * @var \k\http\Session
	 */
	protected $session;

	/**
	 *
	 * @var \k\http\Cookies
	 */
	protected $cookies;

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
	
	protected $useLayout = true;
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
	protected $devController = '\\k\\app\\DevController';
	protected $fallbackController = '\\k\\app\\FallbackController';
	protected $logger;
	protected $frameworkDir;
	
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
		$this->setDir($dir);

		if ($this->configFile) {
			$configFile = $dir . '/' . $this->configFile;
			$this->setConfig($configFile);
		}
		
		if(array_key_exists('debug', $_GET)) {
			register_shutdown_function(function() {
				echo '<pre>';
				var_dump($this->getLogger()->getLogs());
				exit();
			});
		}

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
	 * @return string
	 */
	public function getDir() {
		return $this->dir;
	}
	
	/**
	 * Get app framework dir
	 * 
	 * @param string $dir
	 * @return string
	 */
	public function getFrameworkDir($dir = null) {
		if($this->frameworkDir === null) {
			$cl = new ReflectionClass('\\k\\app\\App');
			$this->frameworkDir = dirname($cl->getFilename());
		}
		if($this->frameworkDir) {
			$fw = $this->frameworkDir;
			if($dir) {
				$fw = $fw . '/' . $dir;
			}
			return $fw;
		}
		return null;
	}

	/**
	 * Set the directory of the app
	 * 
	 * @param string $dir
	 * @param bool $check
	 * @throws InvalidArgumentException
	 */
	public function setDir($dir, $check = false) {
		if ($check && !is_dir($dir)) {
			throw new InvalidArgumentException("$dir is not a directory");
		}
		$this->dir = $dir;
	}

	/**
	 * @return \k\config\PhpConfig
	 */
	public function getConfig() {
		if ($this->config === null) {
			$this->config = new \k\config\PhpConfig();
		}
		return $this->config;
	}
	
	public function getLogger() {
		if($this->logger === null) {
			$this->logger = new \k\log\NullLogger();
		}
		return $this->logger;
	}

	/**
	 * Set a config for this application
	 * 
	 * @param string $file
	 * @return \k\App
	 */
	public function setConfig($file) {
		if (is_file((string) $file)) {
			$this->config = new \k\config\PhpConfig($file);
		} else {
			$this->config = new \k\config\PhpConfig();
		}
		return $this;
	}

	/**
	 * @return Http
	 */
	public function getHttp() {
		if ($this->http === null) {
			$this->http = new \k\http\Http();
		}
		return $this->http;
	}

	/**
	 * @return \k\db\Pdo
	 */
	public function getDb() {
		if ($this->db === null && $this->getConfig()->get('db')) {
			$this->db = new \k\sql\Pdo($this->getConfig()->get('db'));
		}
		return $this->db;
	}

	/**
	 * @return \k\html\View
	 */
	public function getLayout($view = null) {
		if ($this->layout === null) {
			$layoutName = $this->getConfig()->get('view/layout', 'layout/default');
			$this->layout = $this->createView($layoutName);
		}
		if ($this->layout && $view) {
			$this->layout->setVar('content', $view);
		} elseif($view !== null) {
			$this->layout = $view;
		}
		return $this->layout;
	}

	/**
	 * @return \k\http\Session
	 */
	public function getSession() {
		if ($this->session === null) {
			$this->session = new \k\http\Session;
		}
		return $this->session;
	}

	/**
	 * @return \k\http\Cookies
	 */
	public function getCookies() {
		if ($this->cookies === null) {
			$this->cookies = new \k\http\Cookies;
		}
		return $this->cookies;
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
	
	public function getUseLayout() {
		return $this->useLayout;
	}

	public function setUseLayout($useLayout = true) {
		$this->useLayout = $useLayout;
		return $this;
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
		$result = $this->handle($this->getHttp()->getPath(false));

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

	/**
	 * Find a request handler for a given path
	 * 
	 * The handler can be just a view, a controller, or both
	 * 
	 * @param string $path
	 * @return string
	 */
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

		//match to a controller
		$controllerName = $this->defaultController;
		if (!empty($parts)) {
			$controllerName = $parts[0];
		}
		$this->controllerName = $controllerName;
		$this->controller = $this->createController($controllerName, $this->module);
		if ($this->controller) {
			array_shift($parts);
		}
		
		$actionName = $this->defaultAction;
		if (!empty($parts)) {
			$actionName = array_shift($parts);
		}
		$this->actionName = $actionName;
		
		//if we have an ajax request, we don't want a layout
		if($this->getHttp()->isAjax()) {
			$this->setUseLayout(false);
			
			//if we are requesting json data, don't create a view
			if(!$this->getHttp()->accept('application/json')) {
				$this->view = $this->createView($controllerName, $actionName, $moduleName);
			}
		}

		$response = array();
		if ($this->controller) {
			//indexAction...
			$method = lcfirst(str_replace(' ', '', ucwords(preg_replace('/[^A-Z^a-z^0-9]+/', ' ', $actionName))))
			. 'Action';
			$this->action = $method;

			$preResponse = $this->callAction($this->controller, 'pre', array($this->actionName, $parts));
			$methodResponse = $this->callAction($this->controller, $method, $parts);
			$postResponse = $this->callAction($this->controller, 'post', array($this->actionName, $parts));

			$response = array_merge($preResponse, $methodResponse, $postResponse);
		}

		//render with template
		if($this->view) {
			$this->view->addVars($response);
		}
		if($this->getUseLayout()) {
			$this->view = $this->getLayout($this->view);
		}
		if($this->view) {
			$this->registerViewHelpers($this->view);
			return $this->view;
		}
	}

	protected function callAction(Controller $controller, $name, $parts = array()) {
		$r = call_user_func_array(array($controller, $name), $parts);
		if($r instanceof \k\html\View) {
			$this->view = $r;
			return array();
		}
		if($r === null) {
			return array();
		}
		if (!is_array($r)) {
			$r = array('content' => $r);
		}
		if(!isset($r['content'])) {
			$r['content'] = null;
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
		$this->getLogger()->debug('createView : Looking for ' . $filename);
		if (is_file($filename)) {
			$view = new \k\html\View($filename);
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
		if (!class_exists($class)) {
			if($this->fallbackController) {
				$class = $this->fallbackController;
			}
			else {
				return null;
			}
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