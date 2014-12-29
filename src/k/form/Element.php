<?php

namespace k\form;

use \Exception;
use \InvalidArgumentException;

/**
 * Base form element that can contain any html
 */
class Element extends \k\html\Tag {

	/**
	 * @var \k\form\Form
	 */
	protected $form;
	protected $content;
	protected $attributes = [];
	protected $wrap = false;
	protected $groups = [];

	public function __construct(Form $form = null) {
		if ($form) {
			$this->setForm($form);
		}
	}

	public function getForm() {
		return $this->form;
	}

	public function setForm(Form $form) {
		$this->form = $form;
		return $this;
	}

	public function name($v = null) {
		if ($v === null) {
			return $this->getName();
		}
		return $this->setName($v);
	}

	public function getName() {
		$name = $this->getAttribute('name');
		return $name;
	}

	public function setName($v) {
		if(empty($v)) {
			throw new InvalidArgumentException('You must set a name for form elements');
		}
		return $this->setAttribute('name', $v);
	}

	public function getWrap() {
		if ($this->wrap === null && $this->form) {
			$this->wrap = $this->form->getWrap();
		}
		return $this->wrap;
	}

	public function setWrap($v) {
		$this->wrap = $v;
		return $this;
	}

	public function content($v = null) {
		if ($v === null) {
			return $this->getContent();
		}
		return $this->setContent($v);
	}

	public function getContent() {
		return $this->content;
	}

	public function setContent($content) {
		$this->content = $content;
		return $this;
	}

	public function renderContent() {
		return $this->content;
	}

	public function renderHtml() {
		$html = '';
		if ($this->tagName) {
			$html .= $this->openTag();
		}
		$html .= $this->renderContent();
		if ($this->tagName) {
			$html .= $this->closeTag();
		}
		if ($this->getWrap() === true && $this->form) {
			$class = '';
			if($this->hasClass('error')) {
				$class =  ' error';
			}
			$html = '<div class="control-group'.$class.'">'.$html.'</div>';
		}
		return $html;
	}

	/**
	 * Allows you to call a method directly on the parent form
	 * @return \k\form\Element
	 */
	/*
	  public function __call($name, $arguments) {
	  if (property_exists($this, $name)) {
	  $v = isset($arguments[0]) ? $arguments[0] : true;
	  $this->$name = $v;
	  return $this;
	  }
	  if (!$this->form) {
	  throw new Exception('Element ' . get_called_class() . ' not linked to a form. Trying to call ' . $name);
	  }
	  if (!method_exists($this->form, $name)) {
	  throw new Exception('Invalid method called : ' . $name);
	  }
	  return call_user_func_array(array($this->form, $name), $arguments);
	  } */
}