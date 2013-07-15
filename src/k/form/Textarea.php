<?php
namespace k\form;

class Textarea extends Input {

	protected $rows;
	protected $cols;
	protected $readonly;

	public function renderField() {
		$this->form->t($this->placeholder);
		$att = $this->getAttributes(array('rows', 'cols', 'readonly'), 'value');
		$att['text'] = $this->getValue();
		return static::makeTag('textarea', $att);
	}

}
