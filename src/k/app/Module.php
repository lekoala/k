<?php
namespace k\app;

use \Exception;
use \InvalidArgumentException;

class Module {
	/**
	 * @var App
	 */
	protected $app;
	protected $name;
	
	public function __construct(App $app) {
		$this->setApp($app);
	}
	
	/**
	 * @return App
	 */
	public function getApp() {
		return $this->app;
	}
	
	public function setApp($app) {
		$this->app = $app;
		return $this;
	}
	
	public function getName() {
		if($this->name === null) {
			$obj = explode('\\', get_called_class());
			$this->name = end($obj);
		}
		return $this->name;
	}
		
	public function setName($name) {
		$this->name = $name;
		return $this;
	}
	
	public function getSrcDir() {
		return $this->getApp()->getDir() . '/src/modules/' . $this->getName();
	}
	
	protected function config($v,$default = null) {
		return $this->getApp()->getConfig()->get($v,$default);
	}
	
	public function dispatch($params) {
		//if the module have a views dir, look for a template
		$renderer = $this->getApp()->getViewRenderer();
		$viewDir = $renderer->getViewDir() . '/' . strtolower($this->getName());
		if(is_dir($viewDir)) {
			$view = '';
			$parts = $params;
			while(empty($view) && !empty($parts)) {
				$filename = implode('/', $parts);
				$view = $renderer->resolve($filename,$this->getDir());
				array_pop($parts);
			}
			if(empty($view)) {
				$view = $renderer->getDefaultView();
				$view = $renderer->resolve($view,$this->getDir());
			}
			echo '<pre>';
			var_dump($view);
			exit();
			if($view) {
				return $renderer->renderWithLayout($view);
			}
		} 
		
		//if the module have an actions dir, look for actions
		$srcDir = $this->getSrcDir();
		if(is_dir($srcDir)) {
			
		}
	}
}
