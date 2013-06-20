<?php
namespace k\html\form;

/**
 * Base group class
 */
class Group extends Element {
	protected $elements = array();
	
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
}