<?php
namespace K;

/**
 * Class to configure php settings.
 * 
 * Typically, this is mostly an helper to which you pass a config array
 *
 * @author tportelange
 */
class System {
	public function __construct(array $config) {
		foreach($config as $k => $v) {
			//Uppercamel case
			$method = 'set' . str_replace(' ', '', ucwords(preg_replace('/[^A-Z^a-z^0-9]+/', ' ', $k)));
			if(method_exists($this, $method)) {
				$this->$method($v);
			}
			else {
				$this->$k = $v;
			}
		}
	}
	
	public function getTimezone() {
		return date_default_timezone_get();
	}
	
	public function setTimezone($value) {
		return date_default_timezone_set($value);
	}
	
	public function __get($name) {
		return ini_get($name);
	}
	
	public function __set($name, $value) {
		return ini_set($name, $value);
	}
}
