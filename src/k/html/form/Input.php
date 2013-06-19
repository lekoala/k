<?php
namespace k\form;

/**
 * Base input. Split label and field generation for easy extension.
 */
class Input extends Element {

	protected $type = 'text';
	protected $name;
	protected $label;
	protected $class;
	protected $options;
	protected $value;
	protected $defaultValue;
	protected $size;
	protected $append;
	protected $prepend;
	protected $help;
	protected $helpInline;
	protected $placeholder;
	protected $disabled;
	protected $groups = array();

	public function __construct($text = null, \Form $form = null) {
		if (strpos($text, '*') !== false) {
			$text = str_replace('*', '', $text);
			$this->class = 'required';
		}
		$this->name = $this->label = $text;
		//Default label based on name
		$this->label = trim(ucwords(str_replace(array('-', '_', '[', ']'), ' ', $this->label)));
		parent::__construct($text, $form);
	}
	
	public function getGroups() {
		return $this->groups;
	}
	
	public function setGroups($groups) {
		$this->groups = $groups;
		return $this;
	}

	public function getName($groups = true) {
		$name = $this->name;
		if(!empty($this->groups) && $groups) {
			$name = $this->groups[0];
			for($i = 1; $i < count($this->groups); $i++) {
				$name .= '[' . $this->groups[$i] . ']';
			}
			$name .= '[' . $this->name . ']';
		}
		return $name;
	}

	public function getValue() {
		$value = $this->defaultValue;
		if ($this->value) {
			$value = $this->value;
		}
		return $value;
	}

	public function addClass($class) {
		if (!empty($this->class)) {
			$this->class .= ' ' . $class;
		} else {
			$this->class = $class;
		}
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
		if (empty($this->label)) {
			return '';
		}
		$class = '';
		if ($this->form->getWrap()) {
			$class = 'control-label';
		}
		$this->form->t($this->name, $this->label);

		return static::makeTag('label', array(
					'for' => 'input-' . $this->getName(),
					'class' => $class,
					'text' => $this->label
				));
	}

	protected function getAttributes($add = array(), $remove = array()) {
		if (!is_array($add)) {
			$add = array($add);
		}
		if (!is_array($remove)) {
			$remove = array($remove);
		}
		$att = array(
			'type' => $this->type,
			'name' => $this->getName(),
			'value' => $this->getValue(),
			'size' => $this->size,
			'placeholder' => $this->placeholder,
			'disabled' => $this->disabled,
			'class' => $this->class
		);
		foreach ($add as $a) {
			$att[$a] = $this->$a;
		}
		foreach ($remove as $r) {
			unset($att[$r]);
		}
		return $att;
	}

	protected function renderField() {
		$this->form->t($this->placeholder);
		return static::makeTag('input', $this->getAttributes(), true);
	}

	public function renderElement() {
		$html = '';
		if ($this->prepend) {
			$html .= static::makeTag('span.add-on', $this->prepend);
		}
		$html .= $this->renderLabel();
		$html .= $this->renderField();
		if ($this->append) {
			$html .= static::makeTag('span.add-on', $this->append);
		}
		if ($this->help) {
			$this->form->t($this->help);
			$type = 'block';
			if ($this->helpInline) {
				$type = 'inline';
			}
			$html .= '<span class="help-' . $type . '">' . $this->help . '</span>';
		}
		return $html;
	}

}