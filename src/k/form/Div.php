<?php

namespace k\html\form;

/**
 * @method Div class()
 */
class Div extends Group {

	protected $tag = 'div';
	protected $class;
	protected $id;
	
	public function renderElement() {
		$this->attributes['class'] = $this->class;
		$this->attributes['id'] = $this->id;
		
		return $this->renderHtmlTag($this->tag,$this->attributes);
	}

}