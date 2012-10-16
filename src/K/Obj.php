<?php

namespace K;

class Obj {
	
	/**
	 * Get the class name without namespace
	 * 
	 * @param string|object $obj
	 * @return string
	 */
	public static function getClassName($obj) {
		if(is_object($obj)) {
			$obj = get_class($obj);
		}
		if(!is_string($obj)) {
			throw new Exception();
		}
		$obj = explode('\\',$obj);
		return end($obj);
	}
	
	public static function make($var) {
		if(is_object($var)) {
			$var = get_class($var);
		}
		if(class_exists($var)) {
			$var = new $var;
		}
		else {
			throw new Exception($var . ' can not be converted to a class');
		}
		return $var;
	}
	
	public function getClass() {
		return self::getClassName(get_called_class());
	}
	
	/**
	 * If an object extend  this, it's useful to get the class name when echoing 
	 * the classas a default behaviour
	 * 
	 * @return string
	 */
	public function __toString() {
		return $this->getClass();
	}
}