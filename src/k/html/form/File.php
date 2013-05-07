<?php
namespace K\Form;

class File extends Input {

	protected $multiple;
	protected $accept;
	protected $type = 'file';

	protected function renderField() {
		$this->form->t($this->placeholder);
		return static::makeTag('input', $this->getAttributes(array('multiple', 'accept')), true);
	}

	public function setForm($form) {
		parent::setForm($form);
		return $this->form->setEnctype(1);
	}

}