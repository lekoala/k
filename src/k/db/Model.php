<?php

namespace k\db;

/**
 * Model
 *
 * @author tportelange
 */
class Model {
	
	public function getRelated($name) {
		
	}
	
	public function getField($name) {
		if(method_exists($this, $name)) {
			return $this->$name();
		}
		elseif(property_exists($this, $name)) {
			return $this->$name;
		}
		else {
			$o = $this->getRelated($name);
			if ($o) {
				return $o;
			}
		}
	}
	
	public function setField($name, $value) {
		$method = '_' . $name;
		if(method_exists($this, $method)) {
			$this->$method($value);
		}
		elseif(property_exists($this, $name)) {
			$this->$name = $value;
		}
		return $this;
	}
	
	public function __get($name) {
		return $this->getField($name);
	}
	
	public function __set($name, $value) {
		return $this->setField($name, $value);
	}
}
