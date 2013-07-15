<?php

namespace k;

/**
 * Description of Validator
 *
 * @author tportelange
 */
class Validator {
	protected $key;
	public function __construct() {
		
	}
	
	public function key($name) {
		$this->key = $name;
		return $this;
	}
	
	public function addElement($rule, $key = null) {
		if(!$key && $this->key) {
			$this->key = $key;
		}
		if($key) {
			$this->rules[$key] = $rule;
		}
		else {
			$this->rules[] = $rule;
		}
		return $this;
	}
	
	public function email() {
		return $this->addElement(new Validator\Email());
	}
	
	public function blank() {
		return $this->addElement(new Validator\Blank());
	}
	
	public function notBlank() {
		return $this->addElement(new Validator\NotBlank());
	}
	
	public function date($format = null) {
		return $this->addElement(new Validator\Date($format = null));
	}
	
	public function datetime($format = null) {
		return $this->addElement(new Validator\Datetime($format = null));
	}
	
	public function time($format = null) {
		return $this->addElement(new Validator\Time($format = null));
	}
	
	public function email() {
		return $this->addElement(new Validator\Email($format = null));
	}
	
	public function validate($data) {
		
	}
}
