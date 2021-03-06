<?php
namespace k\form;

class Multicheckbox extends Input {

	protected $options = array();

	public function renderElement() {
		$value = $this->getValue();
		$html = '';
		if (!empty($this->label)) {
			$this->form->t($this->label);
			$html = '<p class="label">' . $this->label . '</p>';
		}
		foreach ($this->options as $k => $v) {
			if (is_int($k)) {
				$k = $v;
			}
			$checked = 0;
			if ($k == $value) {
				$checked = 1;
			}
			$this->form->t($v);
			$html .= '<label class="checkbox">';
			$html .= static::makeTag('input', array('type' => 'checkbox', 'class' => $this->class, 'name' => $this->getName() . '[]', 'value' => $k, 'checked' => $checked));
			$html .= ' ' . $v . '</label>';
		}
		return $html;
	}

}