<?php
namespace k;

use \Exception;
use \InvalidArgumentException;

class Module {
	/**
	 * @var App
	 */
	protected $app;
	protected $dir;
	
	public function __construct(App $app, $dir) {
		$dir = $app->getDir() . '/modules/' . $dir;
		$this->setApp($app);
		$this->setDir($dir);
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
	
	public function getDir() {
		return $this->dir;
	}
	
	public function setDir($dir) {
		if(!is_dir($dir)) {
			throw new InvalidArgumentException('Not a directory : ' . $dir);
		}
		$this->dir = $dir;
		return $this;
	}
	
	protected function config($v,$default = null) {
		return $this->getApp()->getConfig()->get($v,$default);
	}
	
	public function dispatch($params) {
		//if the module have a views dir, look for a template
		$renderer = $this->getApp()->getViewRenderer();
		$viewDir = $this->getDir() . '/' . $renderer->getViewDir();
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
			if($view) {
				return $renderer->renderWithLayout($view);
			}
		} 
		
		//if the module have an actions dir, look for actions
		$actionsDir = $this->getDir() . '/' . $this->config('action/dir','actions');
		if(is_dir($actionsDir)) {
			
		}
  		
		
		
	
		$templFile = $renderer->resolve($view,$this->dir);
		if($templFile) {
			return $this->getApp()->getViewRenderer()->renderWithLayout($templFile);
		}
	}
}
