<?php

namespace k\form;

/**
 * Base input with a value that can be populated and grouped
 */
class Input extends Element {

	protected $tagName = 'input';
	protected $attributes = [
		'type' => 'text'
	];
	protected $label;
	protected $defaultValue;
	protected $append;
	protected $prepend;
	protected $help;
	protected $helpInline;
	protected $placeholder;
	protected $disabled;
	protected $wrap = true;

	protected function renderAttributes() {
		//size without width
		if ($this->hasAttribute('size')) {
			$this->addStyle('width:auto');
		}
		return parent::renderAttributes();
	}

	public function getId() {
		static $count = [];
		if (!$this->hasAttribute('id')) {
			$name = str_replace(']','',str_replace('[', '-', $this->getName()));
			if (isset($count[$name])) {
				$count[$name] = $count[$name]++;
				$name .= '-' . $count[$name];
			} else {
				$count[$name] = 1;
			}
			if (empty($name)) {
				$name = '-' . $name;
			}
			$this->setId('input-' . $name);
		}
		return $this->getAttribute('id');
	}
	
	public function placeholder($v) {
		$this->placeholder = $v;
		return $this;
	}

	public function preprend($v) {
		$this->prepend = $v;
		return $this;
	}
	
	public function append($v) {
		$this->append = $v;
		return $this;
	}
	
	public function size($v) {
		return $this->setAttribute('size', $v);
	}

	public function type($v = null) {
		if ($v === null) {
			return $this->getType();
		}
		return $this->setType($v);
	}

	public function getType() {
		return $this->getAttribute('type');
	}

	public function setType($v) {
		return $this->setAttribute('type', $v);
	}

	public function label($value = null) {
		if ($value === null) {
			return $this->getLabel();
		}
		return $this->setLabel($value);
	}

	public function getLabel() {
		if (empty($this->label)) {
			$this->label = trim(ucwords(str_replace(array('-', '_', '[', ']'), ' ', $this->getName())));
		}
		return $this->label;
	}

	public function setLabel($v) {
		if(empty($v)) {
			return $this;
		}
		$this->label = $v;
		return $this;
	}

	public function value($value = null) {
		if ($value === null) {
			return $this->getValue();
		}
		return $this->setValue($value);
	}

	public function getValue($default = null) {
		if ($default === null) {
			$default = $this->defaultValue;
		}
		return $this->getAttribute('value', $default);
	}

	public function setValue($v) {
		return $this->setAttribute('value', $v);
	}

	public function help($value = null, $inline = true) {
		if ($value === null) {
			return $this->help;
		}
		$this->help = $value;
		$this->helpInline = $inline;
		return $this;
	}

	protected function renderLabel() {
		if (!strlen($this->getLabel())) {
			return '';
		}
		$class = '';
		if ($this->getWrap()) {
			$class = 'control-label';
		}
		$for = $this->getId();
		$attributes = compact('class', 'for');
		return '<label ' . $this->_renderAttributes($attributes) . '>' . $this->label . '</label>';
	}

	public function renderContent() {
		return $this->openTag(true);
	}

	public function renderHtml() {
		$html = '';

		if ($this->form->getLayout() == 'horizontal') {
			$html .= '<div class="control-group">';
		}
		$html .= $this->renderLabel();
		if ($this->form->getLayout() == 'horizontal') {
			$html .= '<div class="controls">';
		}
		if ($this->prepend) {
			$html .= '<span class="add-on">' . $this->prepend . '</span>';
		}
		$html .= $this->renderContent();
		if ($this->append) {
			$html .= '<span class="add-on">' . $this->append . '</span>';
		}
		if ($this->help) {
			$this->form->t($this->help);
			$type = 'block';
			if ($this->helpInline) {
				$type = 'inline';
			}
			$html .= '<span class="help-' . $type . '">' . $this->help . '</span>';
		}
		if ($this->form->getLayout() == 'horizontal') {
			$html .= '</div></div>';
		}
		return $html;
	}

}