<?php

namespace k;

use \Exception;
use \InvalidArgumentException;
use \ReflectionClass;
use \ReflectionMethod;

class Module {

	use Bridge;
	
	protected $dir;
	
	public function __construct($dir) {
		$this->setDir($dir);
	}

	public function setDir($dir) {
		$this->dir = $dir;
		return $this;
	}
	
	public function getDir() {
		return $this->dir;
	}
	
	public function getControllers() {
		$iter = new Directory($this->getDir());
		$arr = array();
		foreach($iter as $fi) {
			$classes = util\Obj::getClassesInFile($fi->getPathname());
			if(empty($classes)) {
				continue;
			}
			$name = $classes[0];
			$actions = array();
			$refl = new ReflectionClass($name);
			if($refl) {
				$actions = util\Obj::getDeclaredMethods($name);
			}
			$arr[$name] = $actions; 
		}
		return $arr;
	}
}
