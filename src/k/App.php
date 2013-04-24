<?php

namespace k;

use \RuntimeException;
use \InvalidArgumentException;

class App {
	
	protected $dir;

	/**
	 * @var Config
	 */
	protected $config;

	/**
	 * @var Http
	 */
	protected $http;
	
	/**
	 * @var db\Pdo
	 */
	protected $db;

	protected $user;
	protected $router;
	protected $db;
	protected $debugBar;
	protected $modules = array();
	protected $defaultModule;
	protected $viewRenderer;
	protected $moduleClass = '\k\Module';

	public function __construct($dir) {
		if (!is_dir($dir)) {
			throw new InvalidArgumentException($dir);
		}
		$this->dir = $dir;
		
		if(is_file($dir . '/config.php')) {
			$this->config = new Config($dir . '/config.php');
		}
		else {
			$this->config = new Config();
		}
		
		if($this->config->get('db')) {
			$this->db = new db\Db($this->config->get('db'));
		}
		
		$this->http = new Http();
		$this->viewRenderer = new ViewRenderer();
		$this->viewRenderer->setBaseDir($this->getDir());
		$this->viewRenderer->setDefaultExtension($this->getConfig()->get('view/ext','phtml'));
		$this->viewRenderer->setLayout($this->getConfig()->get('view/layout','layout/default'));

		if(is_dir($this->dir . '/modules')) {
			$this->initModules();
		}
	}
	
	public function getDir() {
		return $this->dir;
	}
	
	/**
	 * @return Config
	 */
	public function getConfig() {
		return $this->config;
	}
	
	/**
	 * @return Http
	 */
	public function getHttp() {
		return $this->http;
	}
	
	/**
	 * @return ViewRenderer
	 */
	public function getViewRenderer() {
		return $this->viewRenderer;
	}
	
	protected function initModules() {
		$modules = $this->config->get('modules',array());
		$this->defaultModule = $this->config->get('default_module','main');
		if(!is_string($this->defaultModule)) {
			throw new InvalidArgumentException('Default module should be a string');
		}
		
		//build module list by reading directory
		if(empty($modules)) {
			$dir = new fs\Directory($this->dir . '/modules');
			foreach($dir as $fi) {
				if($fi->isDir()) {
					$modules[] = $fi->getBasename();
				}
			}
		}
		if(!in_array($this->defaultModule, $modules)) {
			throw new RuntimeException('Default module does not exist : ' . (string) $this->defaultModule);
		}
		
		foreach($modules as $module) {
			$this->modules[$module] = new Module($this,$module);
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
		if(!isset($this->modules[$name])) {
			throw new RuntimeException('Module ' . $name . ' does not exists');
		}
		return $this->modules[$name];
	}

	public function run() {
		$result = $this->handle($this->http->getPath(false));
		echo $result;
	}
	
	public function handle($path) {
		$path = trim($path,'/');
		$parts = explode('/', $path);
		
		//match to a module
		$module = $this->defaultModule;
		$modules = array_keys($this->modules);
		if(!empty($parts[0]) && in_array($module, $modules)) {
			$module = array_shift($parts);
		}
		
		$module = $this->getModule($module);
		return $module->dispatch($parts);
	}

	public function __toString() {
		try {
			return (string) $this->run();
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}

}