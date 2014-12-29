<?php

namespace k\form;

/**
 */
class Fieldset extends Element {

	protected $tagName = 'fieldset';
	protected $legend;

	public function legend($v = null) {
		if ($v === null) {
			return $this->getLegend();
		}
		return $this->setLegend($v);
	}

	public function getLegend() {
		return $this->legend;
	}

	public function setLegend($v) {
		$this->legend = $v;
		return $this;
	}

	public function renderLegend() {
		if (!$this->legend) {
			return '';
		}
		return '<legend>' . $this->legend . '</legend>';
	}

	public function renderHtml() {
		$legend = $this->legend;
		if ($legend) {
			$this->form->t($legend);
			$legend = '<legend>' . $legend . '</legend>';
		}
		return "<fieldset>\n" . $legend;
	}

}