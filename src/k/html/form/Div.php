<?php

namespace k\html\form;

/**
 * @method Div class()
 */
class Div extends Group {

	protected $tag = 'div';
	protected $class;
	
	public function renderElement() {
		$this->attributes['class'] = $this->class;
		
		return $this->renderHtmlTag($this->tag,$this->attributes);
	}

}