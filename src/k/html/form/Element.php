<?php

namespace k\html\form;

class Element {
	/**
	 * @var Formless
	 */
	protected $form;
	protected $text;

	public function __construct($text = null, \k\html\Form $form = null) {
		$this->text = $text;
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

	public function renderElement() {
		return $this->text;
	}

	public function __toString() {
		try {
			return $this->renderElement();
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}

	/**
	 * @return FormlessElement
	 */
	public function __call($name, $arguments) {
		if (property_exists($this, $name)) {
			if (count($arguments) == 0) {
				return $this->$name;
			}
			$this->$name = $arguments[0];
			return $this;
		} else {
			if (!$this->form) {
				throw new Exception('Element not linked to a form');
			}
			if (!method_exists($this->form, $name)) {
				throw new Exception('Invalid method called : ' . $name);
			}
			return call_user_func_array(array($this->form, $name), $arguments);
		}
	}
}