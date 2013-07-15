<?php

namespace k\form;

use \Exception;


/**
 * Base element that can contain any html
 * 
 * @method \k\form\Element content()
 * @method \k\form\Element tag()
 * @method \k\form\Element attributes()
 * @method \k\form\Element form()
 * @method \k\form input()
 * @method \k\form fieldset()
 * @method \k\form checkbox()
 * @method \k\form password()
 * @method \k\form radio()
 * @method \k\form select()
 * @method \k\form div()
 * @method \k\form add()
 */
class Element {

	/**
	 * @var \k\form
	 */
	protected $form;
	protected $content;
	protected $tag;
	protected $attributes = [];
	protected $group;

	public function __construct($content = null, Form $form = null) {
		$this->content = $content;
		if ($form) {
			$this->form = $form;
		}
	}

	public function getForm() {
		return $this->form;
	}

	public function setForm(Form $form) {
		$this->form = $form;
		return $this;
	}
	
	public function getGroup() {
		return $this->group;
	}

	public function setGroup($group) {
		$this->group = $group;
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

	protected function renderHtmlAttributes($attributes) {
		$atts = array();

		//size without width
		if (!empty($attributes['size'])) {
			if (isset($attributes['style'])) {
				$attributes['style'] = rtrim($attributes['style'], ';');
				$attributes['style'] .= ';width:auto';
			} else {
				$attributes['style'] = 'width:auto';
			}
		}
		foreach ($attributes as $k => $v) {
			if(empty($v)) {
				continue;
			}
			if ($k == 'selected' || $k == 'checked' || $k == 'multiple') {
				if ($v) {
					$v = $k;
				}
			}
			$atts[] = $k . '="' . $v . '"';
		}
		return implode(' ', $atts);
	}

	protected function renderHtmlTag($tag, $attributes = null, $close = false) {
		$atts = '';
		$text = '';
		if ($attributes) {
			if(isset($attributes['text'])) {
				$text = $attributes['text'];
				unset($attributes['text']);
			}
			$atts = ' ' . $this->renderHtmlAttributes($attributes);
		}
		$end = '>';
		if(!$text && $close) {
			$end = '/>';
		}
		$html = '<' . $tag . $atts . $end;
		if($text) {
			$html .= $text . '</' . $tag . '>';
		}
		return $html;
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
	 * @return \k\form\Element
	 */
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
	}

}