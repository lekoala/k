<?php

namespace k;

use \RuntimeException;

class App {
	
	protected $dir;

	/**
	 * @var k\Config
	 */
	protected $config;

	/**
	 * @var k\Request
	 */
	protected $request;

	/**
	 * @var k\response
	 */
	protected $response;
	protected $user;
	protected $router;
	protected $db;
	protected $debugBar;
	protected $modules = array();

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

		$this->initModules();
	}
	
	protected function initModules() {
		$modules = $this->config->get('modules',array());
		$defaultModule = $this->config->get('default_module','main');
		if(empty($modules)) {
			$dir = new fs\Directory($this->dir);
			foreach($dir as $fi) {
				if($fi->isDir()) {
					$modules[] = $fi->getBasename();
				}
			}
		}
		if(!in_array($defaultModule, $modules)) {
			throw new RuntimeException('Default module does not exist : ' . $defaultModule);
		}
		foreach($modules as $module) {
			$this->modules[] = new Module($this,$module);
		}
	}

	public function run() {
		$this->request = new Request();
		$this->response = new Response();
		
		$result = $this->handle($this->request->getUrl(false));
		
		return $this->response->send();
	}
	
	public function handle($path) {
		echo '<pre>';
		var_dump($path);
		exit();
	}

	public function __toString() {
		try {
			return (string) $this->run();
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}

}