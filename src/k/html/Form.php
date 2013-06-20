<?php

namespace k\html;

use \Closure;

/**
 * Chainable form builder
 * 
 * @method \k\html\form\Input class()
 * @method \k\html\form\Input type()
 * @method \k\html\form\Input label()
 * @method \k\html\form\Input value()
 * @method \k\html\form\Input defaultValue()
 * @method \k\html\form\Input size()
 * @method \k\html\form\Input append()
 * @method \k\html\form\Input prepend()
 * @method \k\html\form\Input placeholder()
 * @method \k\html\form\Input disabled()
 * @method \k\html\form\Input options()
 * @method \k\html\form\File multiple()
 * @method \k\html\form\File accept()
 * @method \k\html\form\Textarea rows()
 * @method \k\html\form\Textarea cols()
 * @method \k\html\form\Textarea readonly()
 */
class Form extends HtmlWriter {

	const FORM_SEARCH = 'search';
	const FORM_INLINE = 'inline';
	const FORM_HORIZONTAL = 'horizontal';
	const ENCTYPE_MULTIPART_FORM_DATA = 'multipart/form-data';

	protected $action;
	protected $method;
	protected $elements = [];
	protected $wrap = true;
	protected $layout = 'horizontal';
	protected $enctype;
	protected $translations;
	protected $id;
	protected $groups = [];
	protected $attributes = [];

	public function __construct($action = null, $method = 'POST') {
		$this->action = $action;
		$this->method = strtoupper($method);
	}

	/**
	 * Factory method for php < 5.4
	 * @return Form
	 */
	public static function create($action = null, $method = 'POST') {
		return new Form($action, $method);
	}

	/**
	 * Tag creation helper
	 * @param string $name
	 * @param array $attributes
	 * @param bool $close
	 * @return string
	 */
	public static function makeTag($name, $attributes = array(), $close = false) {
		//name.class functionnality
		$parts = explode('.', $name);
		$name = $parts[0];
		if (isset($parts[1])) {
			$attributes['class'] = $parts[1];
		}

		$html = '<' . $name;

		//attributes can be an array or just some text to put inside the tag
		if (is_array($attributes)) {
			//auto id
			if (in_array($name, array('input', 'select')) && !isset($attributes['id']) && isset($attributes['name'])) {
				$attributes['id'] = 'input-' . $attributes['name'];
			}
			if (isset($attributes['id'])) {
				$attributes['id'] = preg_replace('/[^a-z0-9._-]/i', '_', $attributes['id']);
				$attributes['id'] = rtrim($attributes['id'], '_');
			}
			if (isset($attributes['for'])) {
				$attributes['for'] = preg_replace('/[^a-z0-9._-]/i', '_', $attributes['for']);
				$attributes['for'] = rtrim($attributes['for'], '_');
			}

			//size without width
			if (!empty($attributes['size'])) {
				if (isset($attributes['style'])) {
					$attributes['style'] = rtrim($attributes['style'], ';');
					$attributes['style'] .= ';width:auto';
				} else {
					$attributes['style'] = 'width:auto';
				}
			}

			foreach ($attributes as $attName => $attValue) {
				if ($attName == 'text') {
					$text = $attValue;
					continue;
				}
				if ($attName == 'selected' || $attName == 'checked') {
					if ($attValue) {
						$attValue = $attName;
					}
				}
				if (!empty($attValue)) {
					$html .= ' ' . $attName . '="' . $attValue . '"';
				}
			}
		} elseif (is_string($attributes)) {
			$text = $attributes;
		}

		//should the tag be self closed
		if ($close) {
			$html .= '/>';
		} else {
			$html .= '>';
		}
		//if we have text, insert it and close tag
		if (!$close && isset($text)) {
			$html .= $text . '</' . $name . '>';
		}
		//line return
		$html .= "\n";
		return $html;
	}

	public function t(&$label, &$dest = null) {
		if (isset($this->translations[$label])) {
			if ($dest !== null) {
				$dest = $this->translations[$label];
			} else {
				$label = $this->translations[$label];
			}
			return true;
		}
		return false;
	}

	public function getId() {
		if (empty($this->id)) {
			$name = str_replace('/', '-', strtolower(trim($_SERVER['PATH_INFO'], '/')));
			return 'form-' . $name;
		}
		return $this->id;
	}

	public function setId($value) {
		$this->id = $value;
		return $this;
	}

	public function getLayout() {
		return $this->layout;
	}

	public function setLayout($value) {
		if ($value == 'search' || $value == 'inline') {
			$this->setWrap(false);
		}
		$this->layout = $value;
		return $this;
	}

	public function getAttributes() {
		return $this->attributes;
	}

	public function setAttributes($attributes) {
		$this->attributes = $attributes;
		return $this;
	}

	public function getEnctype() {
		return $this->enctype;
	}

	public function setEnctype($value) {
		$this->enctype = $value;
		return $this;
	}

	public function getWrap() {
		return $this->wrap;
	}

	public function setWrap($value) {
		$this->wrap = $value;
		return $this;
	}

	public function getTranslations() {
		return $this->translations;
	}

	public function setTranslations($value) {
		$this->translations = $value;
		return $this;
	}

	public function populateFromPost() {
		foreach ($this->elements as $element) {
			if (!method_exists($element, 'getName')) {
				continue;
			}
			$name = $element->getName();
			if (isset($_POST[$name])) {
				$element->value($_POST[$name]);
			}
		}
		return $this;
	}

	public function dumpTranslationsArray() {
		$translations = array();
		foreach ($this->elements as $element) {
			if (!method_exists($element, 'getName')) {
				continue;
			}
			$name = $element->getName();
			$translations[$name] = $name;
			if ($element instanceof FormAddress) {
				$translations['street'] = 'street';
				$translations['number'] = 'number';
				$translations['zip'] = 'zip';
				$translations['city'] = 'city';
			}
			if (method_exists($element, 'getOptions')) {
				$opts = $element->getOptions();
				//skip large arr
				if (count($opts) > 20) {
					continue;
				}
				foreach ($opts as $i => $o) {
					if (is_numeric($o) || empty($o)) {
						continue;
					}
					$translations[$o] = $o;
				}
			}
			if ($element instanceof FormStartFieldset) {
				$legend = $element->getLegend();
				$translations[$legend] = $legend;
			}
		}
		echo '<pre>';
		var_export($translations);
		echo '</pre>';
		return $this;
	}

	public function input($name, $label = null) {
		$element = new \k\html\form\Input($name);
		return $this->add($element, $label);
	}

	public function email($name = null, $label = null) {
		if (empty($name)) {
			$name = 'email';
		}
		$element = new \k\html\form\Input($name);
		$element->type('email');
		return $this->add($element, $label);
	}

	public function textarea($name, $label = null) {
		$element = new \k\html\form\Textarea($name);
		return $this->add($element, $label);
	}

	public function file($name, $label = null) {
		$element = new \k\html\form\File($name);
		return $this->add($element, $label);
	}

	public function select($name, $label = null) {
		$element = new \k\html\form\Select($name);
		return $this->add($element, $label);
	}

	public function checkbox($name, $label = null) {
		$element = new \k\html\form\Checkbox($name);
		return $this->add($element, $label);
	}

	public function radio($name, $label = null) {
		$element = new \k\html\form\Radio($name);
		return $this->add($element, $label);
	}

	public function multicheckbox($name, $label = null) {
		$element = new \k\html\form\Multicheckbox($name);
		return $this->add($element, $label);
	}

	public function button($label, $class = null) {
		$element = new \k\html\form\Button();
		if ($class) {
			$element->class('btn-' . $class);
		}
		return $this->add($element, $label);
	}

	public function address($name = null, $label = null) {
		if (empty($name)) {
			$name = 'address';
		} else {
			$name = 'address_' . $name;
		}
		$element = new \k\html\form\Address($name);
		return $this->add($element, $label);
	}

	public function submit($label = 'Submit') {
		$element = new \k\html\form\Button();
		$element->type('submit');
		$element->label($label);
		return $this->add($element);
	}

	protected function executeGroupCallback(\k\html\form\Group $el, Closure $closure) {
		$el = $this->add($el);
		array_push($this->groups, $el);
		if (is_callable($closure)) {
			$closure = $closure->bindTo($el, $el);
			$closure();
		}
		array_pop($this->groups);
		return $el;
	}

	public function div($class = null, Closure $closure = null) {
		$element = new \k\html\form\Div();
		if ($closure === null) {
			$closure = $class;
		} else {
			if (is_array($class)) {
				$element->setAttributes($class);
			} elseif ($class) {
				$element->class($class);
			}
		}
		return $this->executeGroupCallback($element, $closure);
	}

	/**
	 * @return \k\html\form\Fieldset
	 */
	public function fieldset($legend = null, Closure $closure = null) {
		$element = new \k\html\form\Fieldset();

		if ($closure === null) {
			$closure = $legend;
		} else {
			if (is_array($legend)) {
				$element->setAttributes($legend);
			} elseif ($legend) {
				$element->legend($legend);
			}
		}
		$el = $this->add($element);
		//
		if (is_callable($closure)) {
			$closure();
		}
		return $el;
	}

	/**
	 * @return \k\html\form\Element
	 */
	public function add($element, $label = null) {
		if (is_string($element)) {
			$element = new \k\html\form\Element($element);
		}
		$element->setForm($this);
		if ($label) {
			$element->attribute('label', $label);
		}
		if (!empty($this->groups)) {
			$last = end($this->groups);
			$last->addElement($element);
		} else {
			$this->elements[] = $element;
		}
		return $element;
	}

	public function renderElement($element) {
		$html = '';
		if ($element instanceof \k\html\form\Input) {
			if ($this->getWrap()) {
				$html .= '<div class="control-group">';
			}
			$html .= $element->renderElement();
			if ($this->getWrap()) {
				$html .= '</div>';
			}
		} elseif ($element instanceof \k\html\form\Group) {
			$html .= $element->renderElement();
			$els = $element->getElements();
			if (!empty($els)) {
				foreach ($els as $el) {
					$html .= $this->renderElement($el);
				}
			}
			$html .= $element->renderCloseTag();
		} else {
			$html .= (string) $element;
		}
		$html .= "\n";


		return $html;
	}

	public function renderHtml() {
		$class = '';
		if ($this->layout) {
			$class = 'form-' . $this->layout;
		}

		$enctype = '';
		if ($this->enctype) {
			$enctype = 'multipart/form-data';
		}
		$html = self::makeTag('form', array(
			'action' => $this->action,
			'method' => $this->method,
			'class' => $class,
			'enctype' => $enctype,
			'id' => $this->getId()
		));
		foreach ($this->elements as $element) {
			$html .= $this->renderElement($element);
		}

		return $html;
	}

}

