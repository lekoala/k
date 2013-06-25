<?php
namespace k\html\form;

/**
 * Base group class
 * @method Group autoclose()
 */
class Group extends Element {
	protected $elements = array();
	protected $autoclose = false;
	
	public function getElements() {
		return $this->elements;
	}

	public function setElements($elements) {
		$this->elements = $elements;
		return $this;
	}
	
	public function addElement($element) {
		$this->elements[] = $element;
		return $this;
	}
	
	/**
	 * Allows you to call a method directly on the parent form
	 * @return \k\html\form\Element
	 */
	public function __call($name, $arguments) {
		if (property_exists($this, $name)) {
			if(empty($arguments)) {
				return $this->$name;
			}
			$this->$name = $arguments[0];
			return $this;
		}
		if (!$this->form) {
			throw new Exception('Element ' . get_called_class() . ' not linked to a form. Trying to call ' . $name);
		}
		if (!method_exists($this->form, $name)) {
			throw new Exception('Invalid method called : ' . $name);
		}
		$el = call_user_func_array(array($this->form, $name), $arguments);
		return $el;
	}
}