<?php
namespace k\form;

class Button extends Element {
	protected $class = 'btn';
	protected $type;
	protected $label;

	public function renderElement() {
		$this->form->t($this->label);
		
		if(empty($this->class)) {
			$this->class = 'btn';
		}

		return static::makeTag('button', array(
					'type' => $this->type,
					'class' => $this->class,
					'text' => $this->label
				));
	}

}