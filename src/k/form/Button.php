<?php

namespace k\form;

class Button extends Element {

	protected $tagName = 'button';
	protected $attributes = [
		'class' => 'btn'
	];
	protected $icon;

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
	
	public function icon($v = null) {
		if ($v === null) {
			return $this->getIcon();
		}
		return $this->setType($v);
	}

	public function getIcon() {
		return $this->icon;
	}

	public function setIcon($v) {
		$this->icon = $v;
		return $this;
	}
	
	public function renderContent() {
		$html = $this->content;
		if($this->icon) {
			$html = '<i class="icon-'.$this->icon.'"/> ' . $html;
		}
		return $html;
	}

}