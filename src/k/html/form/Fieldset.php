<?php
namespace k\html\form;

class Fieldset extends Group {

	protected $tag = 'fieldset';
	
	public function legend($v = null) {
		return $this->attribute('legend',$v);
	}
	
	public function renderElement() {
		$legend = $this->getLegend();
		if ($legend) {
			$this->form->t($this->legend);
			$legend = '<legend>'  . $legend . '</legend>';
		}
		return "<fieldset>\n" . $legend;
	}

}