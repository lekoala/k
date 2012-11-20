<?php
namespace K\Form;

class Checkbox extends Input {

	public function renderElement() {
		$checked = 0;
		$value = $this->getValue();
		if ($value) {
			$checked = 1;
		}
		$this->form->t($this->label);
		$html = static::makeTag('input', array('name' => $this->name, 'type' => 'hidden', 'value' => 0));
		$html .= '<label class="checkbox">';
		$html .= static::makeTag('input', array('type' => 'checkbox', 'class' => $this->class, 'name' => $this->getName(), 'value' => 1, 'checked' => $checked));
		$html .= ' ' . $this->label . '</label>';

		return $html;
	}

}