<?php
namespace k\html\form;

/**
 * @method \k\html\form\Radio options()
 */
class Radio extends Input {

	protected $options = array();
	protected $inline = true;
	
	public function renderElement() {
		$value = $this->getValue();
		$html = '';
		if (!empty($this->label)) {
			$this->form->t($this->label);
			$html = '<p class="control-label">' . $this->label . '</p>';
		}
		if($this->getWrap()) {
			$html .= '<div class="controls">';
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
			$class = 'radio';
			if($this->inline) {
				$class .= ' inline';
			}
			$attributes = array('type' => 'radio', 'class' => $this->class, 'name' => $this->getName() . '[]', 'value' => $k, 'checked' => $checked);
			$html .= '<label class="'.$class.'">';
			$html .= $this->renderHtmlTag('input', $attributes);
			$html .= ' ' . $v . '</label>';
		}
		if($this->getWrap()) {
			$html .= '</div>';
		}
		return $html;
	}

}