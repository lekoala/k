<?php

namespace k;

use \Exception;
use \RuntimeException;
use \InvalidArgumentException;
use \BadMethodCallException;
use \ReflectionClass;

class App {

	use Bridge;

	const NOTICE = 'notice';
	const INFO = 'info';
	const SUCCESS = 'success';
	const ERROR = 'error';

	protected static $instance;
	//
	// properties
	//
	protected $useLayout = true;
	protected $log;
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
	protected $layout;
	protected $profiler;
	//
	// conventions
	//
	protected $defaultAction = 'index';
	protected $defaultController = 'home';
	protected $defaultModule = 'main';
	protected $servicePrefix = 'Service_';
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
			$this->log[] = "found config $c";
			$installed = true;
			$this->config = array_replace_recursive($this->config, require $c);
		}
		$lc = dirname($dir) . '/' . basename($dir) . '.php';
		if (is_file($lc)) {
			$this->log[] = "found local config $lc";
			$installed = true;
			$this->config = array_replace_recursive($this->config, require $lc);
		}
		if (!$installed) {
			throw new AppException('Not installed', AppException::NOT_INSTALLED);
		}
		
		if($this->config('debug')) {
			$this->getProfiler()->start();
		}

		//init
		$this->initModules();
		$this->init();
		$this->log[] = 'App initiliazed';
	}

	/**
	 * Callback for the Dev Toolbar
	 * 
	 * @param DevToolbar $tb
	 * @return array
	 */
	public function devToolbarCallback($tb) {
		$arr =  $this->log;
		array_unshift($arr, count($arr) . ' events');
		return $arr;
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
	
	public function getLog() {
		return $this->log;
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
	
	public function getDefaultModule() {
		return $this->defaultModule;
	}
	
	public function getModules() {
		return $this->modules;
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

	public function getProfiler() {
		if($this->profiler === null) {
			$this->profiler = new Profiler();
		}
		return $this->profiler;
	}

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
			$this->db = new \k\db\Pdo($this->config('db'));
		}
		return $this->db;
	}

	/**
	 * @return \k\View
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
			$this->session = new \k\session\Session;
			session_save_path($this->getTmpDir() . '/sessions');
		}
		return $this->session;
	}

	/**
	 * @return \k\View
	 */
	public function getView() {
		return $this->view;
	}
	
	public function setView($v) {
		$this->view = $v;
	}
	
	/**
	 * @return \k\Controller
	 */
	public function getController() {
		return $this->controller;
	}
	
	/**
	 * @return \k\Module
	 */
	public function getModule() {
		return $this->module;
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
	 * Return the base dir (where is composer.json for instance)
	 * 
	 * @return string
	 */
	public function getBaseDir() {
		if ($this->baseDir === null) {
			$this->baseDir = realpath($this->getDir() . '/../');
		}
		return $this->baseDir;
	}

	/**
	 * Get app framework dir (typically somewhere in your vendor dir)
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

	/**
	 * Get public dir served by apache
	 * 
	 * @return string
	 */
	public function getPublicDir() {
		return $this->getBaseDir() . '/' . $this->publicDirSegment;
	}

	/**
	 * Get data dir (where you store assets)
	 * 
	 * @return string
	 */
	public function getDataDir() {
		return $this->getBaseDir() . '/' . $this->dataDirSegment;
	}

	/**
	 * Get temp dir (where you store files that can be deleted without worries)
	 * 
	 * @return string
	 */
	public function getTmpDir() {
		return $this->getBaseDir() . '/' . $this->tmpDirSegment;
	}

	/////////////////////
	// classes methods //

	/**
	 * Initialize modules. Each module can have a bootstrap that modifies
	 * the behaviour of the app so it is necessary to create all of them
	 * before routing the request.
	 */
	protected function initModules() {
		$modules = $this->config('modules');

		foreach ($modules as $module) {
			$this->modules[$module] = $this->createModule($module);
		}
	}
	
	/**
	 * Get the current request and find matching module/controller/action/view
	 */
	protected function handle($path =null) {
		$request = $this->getRequest();
		$response = $this->getResponse();

		if($path === null) {
			$path = trim($request->getPath(false), '/');
		}
		$parts = explode('/', $path);
		$parts = array_merge(array(),array_filter($parts));

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
		$this->log[] = 'Current module is ' . $this->moduleName;
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
		if ($this->getRequest()->isAjax() 
			|| $this->getRequest()->accept('application/json')
		) {
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
				if($this->controller) {
					$this->log[] = 'Current controller is ' . $this->controllerName;

					//indexAction...
					$method = lcfirst(str_replace(' ', '', ucwords(preg_replace('/[^A-Z^a-z^0-9]+/', ' ', $this->actionName))))
					. 'Action';
					$this->action = $method;

					$preResponse = $this->callAction($this->controller, 'pre', array($this->actionName, $parts));
					$methodResponse = $this->callAction($this->controller, $method, $parts);
					$postResponse = $this->callAction($this->controller, 'post', array($this->actionName, $parts));

					$responseData = array_merge($responseData, $preResponse, $methodResponse, $postResponse);
				}
			} catch (\k\AppException $e) {
				$this->notify($e->getMessage(), self::ERROR);
				$this->getResponse()->redirectBack()->send();
			}
		}

		//without view or controller, return 404
		if (!$this->view && !$this->controller) {
			if($this->config('debug')) {
				throw new AppException('No view or controller');
			}
			else {
				$response->send(404);
			}
		}

		//render with template

		if ($this->view) {
			$this->view->addVars($responseData);
		}
		if ($this->useLayout && php_sapi_name() !== 'cli') {
			$this->log[] = 'Use layout';
			$this->view = $this->getLayout($this->view);
		}
		//could be a view or a view/layout
		if ($this->view) {
			$this->registerViewHelpers($this->view);
			$response->body((string) $this->view);
		} else {
			$response->addData('vars', $responseData);
		}
		$response->send();
	}

	/**
	 * Run app for current request
	 */
	public function run($path = null) {
		$this->handle($path);
	}

	protected function callAction(Controller $controller, $name, $parts = array()) {
		$this->log[] = __METHOD__ . ': ' . $name;
		$r = call_user_func_array(array($controller, $name), $parts);
		if ($r instanceof \k\View) {
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

	/**
	 * Helper that help to format class name
	 * 
	 * @param string $name
	 * @return string
	 */
	protected static function classize($name) {
		return str_replace(' ', '', ucwords(preg_replace('/[^A-Z^a-z^0-9]+/', ' ', $name)));
	}

	/**
	 * Create a view
	 * 
	 * @param string $controller
	 * @param string $action
	 * @param string $module
	 * @return View
	 */
	public function createView($controller, $action = null, $module = null) {
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
		$this->log[] = __METHOD__ . ': ' . $filename;
		if (is_file($filename)) {
			$view = new \k\View($filename);
			$this->log[] = "View created";
		}
		return $view;
	}

	/**
	 * Create a module
	 * 
	 * @param string $name
	 * @return Module
	 */
	protected function createModule($name = null) {
		if ($name === null) {
			$name = $this->moduleName;
		}
		if (array_key_exists($name,$this->modules)) {
			return $this->modules[$name];
		}
		$classname  =self::classize($name);
		$class = $classname . '_Module';
		$this->log[] = __METHOD__ . ': ' . $class;
		$dir = $this->getDir() . '/src/' . $classname;
		if (!class_exists($class)) {
			$o = new Module($dir);
		}
		else {
			$o = new $class($dir);
			$this->log[] = "Module $name created";
		}
		$this->modules[$name] = $o;
		return $o;
	}

	/**
	 * Create a controller
	 * 
	 * @param string $name
	 * @param string $module
	 * @return Controller
	 */
	protected function createController($name = null, $module = null) {
		if ($name === null) {
			$name = $this->controllerName;
		}
		if ($module === null) {
			$module = $this->moduleName;
		}
		$class = self::classize($module) . '_' . self::classize($name);
		$this->log[] = __METHOD__ . ': ' . $class;
		if (!class_exists($class)) {
			//a controller doesn't have to exist
			return null;
		}
		$o = new $class();
		$this->controllers[$name] = $o;
		$this->log[] = "Controller $name created";
		return $o;
	}

	/**
	 * Create a service
	 * 
	 * @param string $name
	 * @return Service
	 * @throws RuntimeException
	 */
	public function createService($name) {
		$class = $this->servicePrefix . self::classize($name);
		if (!isset($this->services[$name])) {
			$this->log[] = __METHOD__ . ': ' . $class;
			if (!class_exists($class)) {
				throw new RuntimeException("Service '$name' does not exist");
			}
			$o = new $class();
			$this->services[$name] = $o;
			$this->log[] = "Service $name created";
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