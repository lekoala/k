<?php

namespace k;

use \Exception;
use \RuntimeException;
use \InvalidArgumentException;

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
	 * Renders view and register view helpers available in all views
	 * @var ViewRenderer
	 */
	protected $viewRenderer;
	protected $user;
	protected $debugBar;
	protected $viewDir = 'views';
	protected $viewExtension = 'phtml';
	protected $modules = array();
	protected $defaultModule;
	protected $defaultController = 'home';
	protected $defaultAction = 'index';
	protected $configFile = 'config.php';
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

		//we use modules
		if (is_dir($this->dir . '/src/modules')) {
			$this->initModules();
		}
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
		if (!$asObject) {
			return (string) $this->dir;
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
	public function getLayout() {
		if ($this->layout === null) {
			$layoutName = $this->getConfig()->get('view/layout', 'layout/default');
			$this->layout = $this->resolveView($layoutName);
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
		if(is_array($text)) {
			$options = $text;
		}
		$options['text'] = $text;
		$options['history'] = false;
		return $this->getSession()->add('notifications', $options);
	}

	public function handle($path) {
		$path = trim($path, '/');
		$originalParts = $parts = explode('/', $path);

		//match to a module
		if (!empty($this->modules)) {
			$module = $this->defaultModule;
			$modules = array_keys($this->modules);
			if (!empty($parts[0]) && in_array($module, $modules)) {
				$module = array_shift($parts);
			}

			$module = $this->getModule($module);
			return $module->dispatch($parts);
		}

		//use default routing
		//match template
		$view = $layout = null;
		//if we have an ajax request requesting json, don't bother to create a view
		if (!($this->getHttp()->isAjax() && $this->getHttp()->accept('application/json'))) {
			$view = $this->resolveView($originalParts); //this will be passed to the controller
			//wrap in layout
			$layout = $this->getLayout();
			if ($layout) {
				$layout->setVar('content', $view);
			}
		}

		//match controller
		$ctrlClass = $this->defaultController;
		if (!empty($parts)) {
			$ctrlClass = array_shift($parts);
		}
		$ctrlClass = $this->resolveController($ctrlClass);
		if (!class_exists($ctrlClass) && $this->genericController) {
			$ctrlClass = $this->resolveController($this->genericController);
		}
		if (class_exists($ctrlClass)) {
			$c = new $ctrlClass($this);
			if ($view) {
				$c->setView($view);
			}
			if ($layout) {
				$c->setLayout($layout);
			}

			$action = $this->defaultAction;
			if (!empty($parts)) {
				$action = array_shift($parts);
			}

			//something like indexGet, loginPost, userDelete
			$action = lcfirst(str_replace(' ', '', ucwords(preg_replace('/[^A-Z^a-z^0-9]+/', ' ', $action))))
					. 'Action';

			$res = array();

			if (method_exists($c, 'pre')) {
				$preResponse = $c->pre();
			}

			if (method_exists($c, $action)) {
				//TODO: add reflection for parameters
				$response = $c->$action();
			}

			if (is_array($preResponse)) {
				$response = array_merge($preResponse, $response);
			}

			//if the controller implements our controller
			if ($c instanceof Controller) {
				//get view and options for rendering
			}
		}

		//render with template
		if ($view) {
			$view->addVars($response);
			if ($layout) {
				$this->registerViewHelpers($layout);
				return $layout->render();
			}
			$this->registerViewHelpers($view);
			return $view->render();
		}
	}

	protected function resolveView($parts, $module = null) {
		if (is_string($parts)) {
			$parts = explode('/', $parts);
		}

		$view = '';
		$viewDir = $this->getDir() . '/' . $this->viewDir;
		if (is_dir($viewDir)) {
			if (empty($parts) || $parts[0] == '') {
				$parts = array($this->defaultAction);
			}
			while (empty($view) && !empty($parts)) {
				$filename = implode('/', $parts);

				$filename .= '.' . $this->viewExtension;

				$filename = $viewDir . '/' . $filename;

				if (is_file($filename)) {
					$view = new View($filename);
				}

				array_pop($parts);
			}
			return $view;
		}
		return false;
	}

	protected function resolveController($name, $module = null) {
		$class = '';
		if ($this->controllerPrefix) {
			$class .= $this->controllerPrefix;
		}
		if ($module) {
			$class .= $this->modulePrefix;
		}
		$class .= str_replace(' ', '', ucwords(preg_replace('/[^A-Z^a-z^0-9]+/', ' ', $name)));
		return $class;
	}

	public function resolveService($name) {
		$class = $this->servicePrefix . str_replace(' ', '', ucwords(preg_replace('/[^A-Z^a-z^0-9]+/', ' ', $name)));
		if (!class_exists($class)) {
			throw new RuntimeException("Service '$name' does not exist");
		}
		if (!isset($this->services[$name])) {
			$o = new $class();
			if (!$o instanceof service\Service) {
				throw new RuntimeException("Service $name must extend base service class");
			}
			$o->setApp($this);
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