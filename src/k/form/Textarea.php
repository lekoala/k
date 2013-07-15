<?php

namespace k\form;

class Textarea extends Input {

	protected $attributes = [
		'rows' => '',
		'cols' => '',
		'readonly' => ''
	];
	protected $value;

	public function getValue($default = null) {
		if ($default === null) {
			$default = $this->defaultValue;
		}
		if (!$this->value) {
			return $default;
		}
		return $this->value;
	}

	public function setValue($v) {
		return $this->value = $v;
	}

	public function renderContent() {
		return '<textarea ' . $this->renderAttributes() . '>' . $this->getValue() . '</textarea>';
	}

}
