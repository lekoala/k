<?php
namespace K\Form;
/**
 * Base element that can contain any html
 * 
 * @method Element content()
 * @method \K\Form input()
 * @method \K\Form button()
 * @method \K\Form checkbox()
 * @method \K\Form radio()
 * @method \K\Form add()
 * @method \K\Form address()
 * @method \K\Form email()
 * @method \K\Form file()
 * @method \K\Form openFieldset()
 * @method \K\Form closeFieldset()
 * @method \K\Form openActions()
 * @method \K\Form closeActions()
 * @method \K\Form openGroup()
 * @method \K\Form closeGroup()
 * @method Input class()
 * @method Input type()
 * @method Input label()
 * @method Input value()
 * @method Input defaultValue()
 * @method Input size()
 * @method Input append()
 * @method Input prepend()
 * @method Input placeholder()
 * @method Input disabled()
 * @method Input options()
 * @method File multiple()
 * @method File accept()
 * @method Textarea rows()
 * @method Textarea cols()
 * @method Textarea readonly()
 */
class Element {

	/**
	 * @var Form
	 */
	protected $form;
	protected $text;

	public static function makeTag($name, $attributes = array(), $close = false) {
		return \K\Form::makeTag($name, $attributes, $close);
	}
	
	public function __construct($text = null, Form $form = null) {
		$this->text = $text;
		if ($form) {
			$this->form = $form;
		}
	}
	
	public function getForm() {
		return $this->form;
	}

	public function setForm($form) {
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
	 * @return FormElement
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
				throw new \Exception('Element not linked to a form');
			}
			if (!method_exists($this->form, $name)) {
				throw new \Exception('Invalid method called : ' . $name);
			}
			return call_user_func_array(array($this->form, $name), $arguments);
		}
	}

}