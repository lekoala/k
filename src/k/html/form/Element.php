<?php

namespace k\html\form;

use \Exception;

class Element {

	/**
	 * @var \k\html\Form
	 */
	protected $form;
	protected $content;
	protected $tag;
	protected $attributes;

	public function __construct($content = null, \k\html\Form $form = null) {
		$this->content = $content;
		if ($form) {
			$this->form = $form;
		}
	}

	public function getForm() {
		return $this->form;
	}

	public function setForm(\k\html\Form $form) {
		$this->form = $form;
		return $this;
	}

	public function getContent() {
		return $this->content;
	}

	public function setContent($content) {
		$this->content = $content;
		return $this;
	}

	public function getTag() {
		return $this->tag;
	}

	public function setTag($tag) {
		$this->tag = $tag;
		return $this->tag;
	}

	public function getAttributes() {
		return $this->attributes;
	}

	public function setAttributes($attributes) {
		$this->attributes = $attributes;
		return $this;
	}

	public function getAttribute($name) {
		if (isset($this->attributes[$name])) {
			return $this->attributes[$name];
		}
	}

	public function setAttribute($name, $value) {
		$this->attributes[$name] = $value;
		return $this;
	}

	public function attribute($name, $value = false) {
		if ($value !== false) {
			return $this->setAttribute($name, $value);
		}
		return $this->getAttribute($name);
	}

	protected function renderHtmlAttributes($atts) {
		$atts = array();
		foreach ($this->attributes as $k => $v) {
			$atts[] = $k . '="' . $v . '"';
		}
		return implode(' ', $atts);
	}

	protected function renderHtmlTag($tag, $attributes = null) {
		$atts = '';
		if ($attributes) {
			$atts = ' ' . $this->renderHtmlAttributes($attributes);
		}
		return '<' . $tag . $atts . '>';
	}

	public function renderOpenTag() {
		if (!$this->tag) {
			return '';
		}
		return $this->renderHtmlTag($this->getTag(), $this->getAttributes());
	}

	public function renderCloseTag() {
		return '</' . $this->getTag() . '>';
	}

	public function renderElement() {
		return $this->content;
	}

	public function __toString() {
		try {
			return $this->renderElement();
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}

	/**
	 * Allows you to call a method directly on the parent form
	 * @return \k\html\form\Element
	 */
	public function __call($name, $arguments) {
		if(property_exists($this, $name)) {
			$this->$name = $arguments[0];
			return true;
		}
		if (!$this->form) {
			throw new Exception('Element ' . get_called_class() . ' not linked to a form. Trying to call ' . $name);
		}
		if (!method_exists($this->form, $name)) {
			throw new Exception('Invalid method called : ' . $name);
		}
		return call_user_func_array(array($this->form, $name), $arguments);
	}

}