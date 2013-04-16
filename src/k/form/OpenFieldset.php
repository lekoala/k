<?php
namespace K\Form;

/**
 * @method OpenFieldset legend()
 */
class OpenFieldset extends Element {

	protected $legend;
	
	public function getLegend() {
		return $this->legend;
	}

	public function renderElement() {
		$legend = '';
		if ($this->legend) {
			$this->form->t($this->legend);
			$legend = static::makeTag('legend', $this->legend);
		}
		return "<fieldset>\n" . $legend;
	}

}