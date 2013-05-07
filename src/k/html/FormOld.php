<?php

namespace k;

class form {
	const METHOD = 'method';
	const METHOD_GET = 'get';
	const METHOD_POST = 'post';
	const ACTION = 'action';
	const _CLASS = 'class';
	const CLASS_VERTICAL = 'form-vertical';
	const CLASS_INLINE = 'form-inline';
	const CLASS_SEARCH = 'form-search';
	const CLASS_HORIZONTAL = 'form-horizontal';
	
	protected $method = 'post';
	protected $class = 'form-vertical';
	protected $action = '';
	protected $elements = array();
	
	function __construct($params = array()) {
		foreach($params as $k => $v) {
			if($v instanceof form_element) {
				$this->add_element($v);
			}
			elseif(isset($this->$k)) {
				$this->$k = $v;
			}
		}
	}
	
	function add_element($el) {
		$this->elements[] = $el;
	}
	
	function render() {
		$html = '<form action="'.$this->action.'" method="'.$this->method.'" class="'.$this->class.'">';
		foreach($this->elements as $element) {
			$html .= $element->render();
		}
		$html .= '</form>';
		return $html;
	}
	
	function __toString() {
		return $this->render();
	}
}