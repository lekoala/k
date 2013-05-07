<?php

namespace k;

class FormElement {
	protected $name;
	protected $label;
	protected $id;
	protected $value;
	protected $help;
	protected $type = 'text';
	
	function __construct($name, $type = null, $label = null, $help = null, $value = null, $id = null) {
		if(empty($type)) {
			$type = $this->guessType($name);
		}
		if(empty($label)) {
			$label = $this->name_to_label($name);
		}
		if(empty($id)) {
			$id = 'input-' . $name;
		}
		if(empty($value)) {
			if(isset($_GET[$name])) {
				$value = $_GET[$name];
			}
			elseif(isset($_POST[$name])) {
				$value = $_POST[$name];
			}
		}
		$this->name = $name;
		$this->type = $type;
		$this->value = $value;
		$this->help = $help;
		$this->label = $label;
		$this->id = $id;
	}
	
	function name_to_label($name) {
		$name = str_replace('_', '', $name);
		$name = str_replace('-', '', $name);
		$name = ucwords($name);
		return $name;
	}
	
	function render() {
		switch($this->type) {
			case 'submit':
				$html = '<div class="control-group">
	<input type="'.$this->type.'" name="'.$this->name.'" id="'.$this->id.'" value="'.$this->value.'">
</div>';
				break;
			default : 
				$html = '<div class="control-group">
	<label for="'.$this->id.'">'.$this->label.'</label>
	<input type="'.$this->type.'" name="'.$this->name.'" id="'.$this->id.'" value="'.$this->value.'">
	<span class="help-inline">'.$this->help.'</span>
</div>';
		}
		
		return $html;
	}
	
	function guessType($name) {
		$type = 'text';
		
		if($name == 'password'
				|| strpos($name, '_password') !== false) {
			$type = 'password';
		}
		if($name == 'submit'
				|| strpos($name, 'submit_') !== false) {
			$type = 'submit';
		}
		elseif(strpos($name, 'is_') !== false) {
			$type = 'checkbox';
		}
		elseif(strpos($name, '_list') !== false) {
			$type = 'select';
		}
		
		return $type;
	}
	
	function __toString() {
		return $this->render();
	}
}