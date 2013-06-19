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
		if(empty($dir)) {
			throw new Exception("Directory must not be empty");
		}
		$this->dir = $dir;
		return $this;
	}

	public function getDir() {
		return $this->dir;
	}

	public function getName() {
		return strtolower(basename($this->dir));
	}
	
	public function getNav() {
		$arr = $this->getControllers(false);
		$menu = [];
		$name = $this->getName();
		$prefix = 'Module_' . ucfirst($this->getName()) . '_';
		foreach($arr as $item) {
			$n = str_replace($prefix,'',$item);
			if($n == 'Home') {
				continue;
			}
			$menu[$n] = array(
				'name' => $n,
				'link' => '/' . $name . '/' . strtolower($n)
			);
		}
		return $menu;
	}

	public function getControllers($withActions = true) {
		$dir = $this->getDir();
		$appdir = $this->getApp()->getDir();
		$iter = $this->createDir($dir);
		$arr = array();
		foreach ($iter as $fi) {
			if($fi->isDir() || strpos($fi->getBasename(), '.') === 0) {
				continue;
			}
			$name = trim(str_replace($appdir,'',$fi->getPath() . '/' . $fi->getBasename('.php')),'/');
			$name = str_replace('/', '_', $name);
			
			if ($withActions) {
				$actions = array();

				$refl = new ReflectionClass($name);
				if ($refl) {
					$actions = util\Obj::getDeclaredMethods($name);
				}
				$arr[$name] = $actions;
			} else {
				$arr[] = $name;
			}
		}
		return $arr;
	}

}
