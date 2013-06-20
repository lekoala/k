<?php
namespace k\html\form;

/**
 * @method \k\html\form\Radio options()
 */
class Radio extends Input {

	protected $options = array();
	
	public function renderElement() {
		$value = $this->getValue();
		$html = '';
		if (!empty($this->label)) {
			$this->form->t($this->label);
			$html = '<p>' . $this->label . '</p>';
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
			$attributes = array('type' => 'radio', 'class' => $this->class, 'name' => $this->getName() . '[]', 'value' => $k, 'checked' => $checked);
			$html .= '<label class="radio">';
			$html .= $this->renderHtmlTag('input', $attributes);
			$html .= ' ' . $v . '</label>';
		}
		return $html;
	}

}