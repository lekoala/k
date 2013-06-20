<?php
namespace k\html\form;

/**
 * @method Fieldset legend()
 */
class Fieldset extends Group {

	protected $tag = 'fieldset';
	protected $legend;
	
	public function renderElement() {
		$legend = $this->legend;
		if ($legend) {
			$this->form->t($legend);
			$legend = '<legend>'  . $legend . '</legend>';
		}
		return "<fieldset>\n" . $legend;
	}

}