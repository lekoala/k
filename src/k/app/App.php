<?php

namespace k\app;

use \Exception;
use \RuntimeException;
use \InvalidArgumentException;
use \BadMethodCallException;
use \ReflectionClass;
use \DirectoryIterator;

class App {

	use Bridge;

	const NOTICE = 'notice';
	const INFO = 'info';
	const SUCCESS = 'success';
	const ERROR = 'error';

	protected static $instance = null;
	//
	// properties
	//
	protected $useLayout = true;
	protected $module;
	protected $moduleName;
	protected $action;
	protected $actionName;
	protected $controller;
	protected $controllerName;
	protected $config = array(
		'debug' => 1,
		'large_file_limit' => 9216,
		'modules' => array('main')
	);
	//
	// classes
	//
	protected $request;
	protected $response;
	protected $logger;
	protected $view;
	protected $http;
	protected $db;
	protected $session;
	protected $cookies;
	protected $layout;
	//
	// conventions
	//
	protected $defaultAction = 'index';
	protected $defaultController = 'home';
	protected $defaultModule = 'main';
	protected $servicePrefix = 'Service_';
	protected $modelPrefix = 'Model_';
	protected $viewDir = 'views';
	protected $viewExtension = 'phtml';
	//
	// cache
	//
	protected $controllers = array();
	protected $services = array();
	protected $modules = array();
	//
	// app directories
	//
	protected $dir;
	protected $baseDir;
	protected $frameworkDir;
	protected $dataDirSegment = 'data';
	protected $tmpDirSegment = 'tmp';
	protected $publicDirSegment = 'public';

	/**
	 * Create a new app
	 * 
	 * @param string $dir If omitted, will use the parent folder
	 * @throws InvalidArgumentException
	 */
	public function __construct($dir = null) {
		self::$instance = $this;

		if (!$dir) {
			$dir = $this->getDir();
		}

		//config
		$c = $dir . '/config.php';
		$installed = false;
		if (is_file($c)) {
			$installed = true;
			$this->config = array_replace_recursive($this->config, require $c);
		}
		$lc = dirname($dir) . '/' . basename($dir) . '.php';
		if (is_file($lc)) {
			$installed = true;
			$this->config = array_replace_recursive($this->config, require $lc);
		}
		if(!$installed) {
			$this->error(AppException::NOT_INSTALLED);
		}
		
		$this->initModules();
		$this->init();
		$this->getLogger()->debug('App initiliazed');
	}
	
	public function error($code = 1, $message = 'Unknown error') {
		throw new AppException($message,$code);
	}

	/**
	 * @return static
	 */
	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/////////////////////////////////////
	// method to implement in subclass //

	public function init() {
		//implement in subclass
	}

	public function registerViewHelpers($r) {
		//implement in subclass and add to view renderer
	}

	////////////////////////
	// properties getters //

	public function getConfig() {
		return $this->config;
	}

	public function getUseLayout() {
		return $this->useLayout;
	}

	public function setUseLayout($useLayout = true) {
		$this->useLayout = $useLayout;
		return $this;
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

	public function getUrlSegment() {
		$url = $this->moduleName;
		if ($url === $this->defaultModule) {
			$url = '';
		}
		if ($this->controllerName) {
			$url .= '/' . $this->controllerName;
		}
		return $url;
	}

	/////////////////////
	// classes getters //

	/**
	 * @return \psr\log\LoggerInterface
	 */
	public function getLogger() {
		if ($this->logger === null) {
			$this->logger = new \k\log\NullLogger();
		}
		return $this->logger;
	}

	public function getRequest() {
		if ($this->request === null) {
			$this->request = new Request();
		}
		return $this->request;
	}

	/**
	 * 
	 * @return k\app\Response
	 */
	public function getResponse() {
		if ($this->response === null) {
			$this->response = new Response($this->config('response', array()));
		}
		return $this->response;
	}

	/**
	 * @return \k\db\Pdo
	 */
	public function getDb() {
		if ($this->db === null && $this->config('db')) {
			$this->db = new \k\sql\Pdo($this->config('db'));
		}
		return $this->db;
	}

	/**
	 * @return \k\html\View
	 */
	public function getLayout($view = null) {
		if ($this->layout === null) {
			$layoutName = $this->config('view/layout', 'layout/default');
			$this->layout = $this->createView($layoutName);
			$this->layout->content = '';
		}
		if ($this->layout && $view) {
			$view->setParent($this->layout);
		} elseif ($view !== null) {
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
			session_save_path($this->getTmpDir() . '/sessions');
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

	/**
	 * @return \k\html\View
	 */
	public function getView() {
		return $this->view;
	}

	/////////////////////////////
	// app directories getters //

	/**
	 * Return the dir where the app lives
	 * 
	 * @return string
	 */
	public function getDir() {
		if ($this->dir === null) {
			$cl = new ReflectionClass($this);
			$this->dir = dirname(dirname($cl->getFileName()));
		}
		return $this->dir;
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
	 * Return the base dir
	 * 
	 * @return string
	 */
	public function getBaseDir() {
		if ($this->baseDir === null) {
			$this->baseDir = realpath($this->getDir() . '/../');
		}
		return $this->baseDir;
	}

	///////////////////
	// cache getters //

	/**
	 * Get app framework dir
	 * 
	 * @param string $dir
	 * @return string
	 */
	public function getFrameworkDir($dir = null) {
		if ($this->frameworkDir === null) {
			$cl = new ReflectionClass('\\k\\app\\App');
			$this->frameworkDir = dirname($cl->getFilename());
		}
		if ($this->frameworkDir) {
			$fw = $this->frameworkDir;
			if ($dir) {
				$fw = $fw . '/' . $dir;
			}
			return $fw;
		}
		return null;
	}

	public function getPublicDir() {
		return $this->getBaseDir() . '/' . $this->publicDirSegment;
	}

	public function getDataDir() {
		return $this->getBaseDir() . '/' . $this->dataDirSegment;
	}

	public function getTmpDir() {
		return $this->getBaseDir() . '/' . $this->tmpDirSegment;
	}

	/////////////////////
	// classes methods //

	protected function initModules() {
		$modules = $this->config('modules');

		foreach ($modules as $module) {
			$this->modules[$module] = $this->createModule($module);
		}
	}

	public function handle() {
		$request = $this->getRequest();
		$response = $this->getResponse();

		$path = trim($request->getPath(false), '/');
		$parts = explode('/', $path);
		$parts = array_filter($parts);

		if (isset($_GET['_ajax'])) {
			$this->getRequest()->forceAjax();
		}
		if (isset($_GET['_json'])) {
			$this->getRequest()->forceType('application/json');
		}
		
		//match to a module if we are using them
		$modules = array_keys($this->modules);
		if (!empty($parts[0]) && in_array($parts[0], $modules)) {
			$this->moduleName = array_shift($parts);
		} else {
			$this->moduleName = $this->defaultModule;
		}

		$this->module = $this->createModule($this->moduleName);

		//match to a controller
		$this->controllerName = $this->defaultController;
		if (!empty($parts)) {
			$this->controllerName = array_shift($parts);
		}
		$this->actionName = $this->defaultAction;
		if (!empty($parts)) {
			$this->actionName = array_shift($parts);
		}

		//if we have an ajax request, we don't want a layout
		if ($this->getRequest()->isAjax() || $this->getRequest()->accept('application/json')) {
			$this->useLayout = false;
			$response->type('application/json');
		}
		//if we are requesting json data, don't create a view
		if (!$this->getRequest()->accept('application/json')) {
			$this->view = $this->createView($this->controllerName, $this->actionName, $this->moduleName);
		}

		$responseData = array();
		if ($this->config('debug')) {
			$responseData['debug'] = array();
		}

		if ($this->controllerName) {

			try {
				$this->controller = $this->createController();
				//indexAction...
				$method = lcfirst(str_replace(' ', '', ucwords(preg_replace('/[^A-Z^a-z^0-9]+/', ' ', $this->actionName))))
				. 'Action';
				$this->action = $method;

				$preResponse = $this->callAction($this->controller, 'pre', array($this->actionName, $parts));
				$methodResponse = $this->callAction($this->controller, $method, $parts);
				$postResponse = $this->callAction($this->controller, 'post', array($this->actionName, $parts));

				$responseData = array_merge($responseData, $preResponse, $methodResponse, $postResponse);
			} catch (\k\app\AppException $e) {
				$this->notify($e->getMessage(),self::ERROR);
				$this->getResponse()->redirectBack()->send();
			}
		}
		
		//without view or controller, return 404
		if (!$this->view && !$this->controller) {
			$response->send(404);
		}

		//render with template //

		if ($this->view) {
			$this->view->addVars($responseData);
		}
		if ($this->useLayout) {
			$this->view = $this->getLayout($this->view);
		}
		//could be a view or a view/layout
		if ($this->view) {
			$this->registerViewHelpers($this->view);
			$response->body((string) $this->view);
		} else {
			$response->addData('vars',$responseData);
		}
		$response->send();
	}

	/**
	 * Run app for current request
	 */
	public function run() {
		try {
			$this->handle();
		} catch (\k\app\AppException $e) {
			$this->notify($e->getMessage());
			$this->getResponse()->redirectBack();
		}
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
		if ($this->getRequest()->accept('application/json')) {
			return $this->getResponse()->addData('notifications', $options);
		}
		return $this->getSession()->add('notifications', $options);
	}

	protected function callAction(Controller $controller, $name, $parts = array()) {
		$r = call_user_func_array(array($controller, $name), $parts);
		if ($r instanceof \k\html\View) {
			$this->view = $r;
			return array();
		}
		if ($r === null) {
			return array();
		}
		if (!is_array($r)) {
			$r = array('content' => $r);
		}
		if (!isset($r['content'])) {
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
		$filename = $this->getDir();
		if ($module) {
			$filename .= '/src/' . ucfirst($module);
		}
		$filename .= '/' . $this->viewDir;
		$filename .= '/' . $name;
		$filename .= '.' . $this->viewExtension;
		$this->getLogger()->debug(__METHOD__ . ': ' . $filename);
		if (is_file($filename)) {
			$view = new \k\html\View($filename);
			$this->getLogger()->debug('View created');
		}
		return $view;
	}

	protected static function classize($name) {
		return str_replace(' ', '', ucwords(preg_replace('/[^A-Z^a-z^0-9]+/', ' ', $name)));
	}

	protected function createModule($name = null) {
		if ($name === null) {
			$name = $this->moduleName;
		}
		if (isset($this->modules[$name])) {
			return $this->modules[$name];
		}
		$class = self::classize($name) . '_Module';
		$this->getLogger()->debug(__METHOD__ . ': ' . $class);
		if (!class_exists($class)) {
			return null;
		}
		$o = new $class();
		$this->modules[$name] = $o;
		return $o;
	}

	protected function createController($name = null, $module = '*') {
		if ($name === null) {
			$name = $this->controllerName;
		}
		if ($module === '*') {
			$module = $this->moduleName;
		}
		$class = self::classize($module) . '_' . self::classize($name);
		$this->getLogger()->debug(__METHOD__ . ': ' . $class);
		if (!class_exists($class)) {
			return null;
		}
		$o = new $class();
		$this->controllers[$name] = $o;
		$this->getLogger()->debug('Controller created');
		return $o;
	}

	public function createService($name) {
		$class = $this->servicePrefix . str_replace(' ', '', ucwords(preg_replace('/[^A-Z^a-z^0-9]+/', ' ', $name)));
		if (!class_exists($class)) {
			throw new RuntimeException("Service '$name' does not exist");
		}
		if (!isset($this->services[$name])) {
			$o = new $class();
			if (!$o instanceof \k\app\Service) {
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

	public function __call($name, $arguments) {
		throw new BadMethodCallException("$name does not exist on app");
	}

}