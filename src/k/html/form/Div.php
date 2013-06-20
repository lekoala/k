<?php

namespace k\html\form;

/**
 * @method Div class()
 */
class Div extends Group {

	protected $tag = 'div';

	public function cls($v = null) {
		return $this->attribute('class',$v);
	}
	
	public function getClass() {
		return $this->getAttribute('class');
	}

	public function setClass($class) {
		return $this->setAttribute('class', $class);
	}

	public function renderElement() {
		return $this->renderHtmlTag($this->tag, $this->getAttributes());
	}

}