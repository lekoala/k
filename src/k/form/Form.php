<?php

namespace k\form;

use \Closure;

/**
 * Chainable form builder
 * 
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
 * 
 * @link http://anahkiasen.github.io/former/
 * @link https://github.com/zendframework/zf2/blob/master/library/Zend/Form/Form.php
 */
class Form extends \k\html\Tag {

	const FORM_SEARCH = 'search';
	const FORM_INLINE = 'inline';
	const FORM_HORIZONTAL = 'horizontal';
	const ENCTYPE_MULTIPART_FORM_DATA = 'multipart/form-data';

	protected $action;
	protected $actions = [];
	protected $method = 'POST';
	protected $elements = [];
	protected $wrap = true;
	protected $layout = 'horizontal';
	protected $enctype;
	protected $translations;
	protected $groups = [];

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
		static $count = 0;

		if ($this->id === null) {
			$name = str_replace('/', '-', strtolower(trim(isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : null, '/')));
			if (++$count > 1 || empty($name)) {
				$name .= '-' . $count;
			}
			if (empty($name)) {
				$name = '-' . $name;
			}
			$this->id = 'form' . $name;
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

	public function find($name, $el = null) {
		if ($el === null) {
			$el = $this->elements;
		}
		if (is_array($el)) {
			foreach ($el as $element) {
				if ($element instanceof Group) {
					$ret = $this->find($name, $element->getElements());
					if ($ret) {
						return $ret;
					}
				} else {
					if (!method_exists($element, 'getName')) {
						continue;
					}
					if ($name == $element->getName()) {
						return $element;
					}
				}
			}
		}
		return false;
	}

	public function populate($data = null, $el = null) {
		if ($el === null) {
			$el = $this->elements;
		}

		if (is_object($data)) {
			//orm integration
			if ($data instanceof \k\db\Orm) {
				//populate has one in fields
				$rels = $data->getHasOneRelations();
				foreach ($rels as $rel => $class) {
					$field = $class::getForForeignKey($rel);
					$f = $this->find($field);
					if ($f) {
						$o = $data->$rel();
						$v = $o->getId();
						$f->value($v);
						$f->attribute('data-label', $o->getLabel());
					}
				}
			}
			//model integration
			if ($data instanceof \k\Model) {
				//automatically bind validation rules
				$validations = $data::getValidation();
				foreach ($validations as $field => $rules) {
					$f = $this->find($field);
					if ($f) {
						foreach ($rules as $name => $v) {
							$f->attribute('data-' . $name, $v);
						}
					}
				}
			} else {
				$data = (array) $data;
			}
		}

		if (is_array($el)) {
			foreach ($el as $element) {
				if ($element instanceof Group) {
					$this->populate($data, $element->getElements());
				} else {
					if (!method_exists($element, 'getName')) {
						continue;
					}
					$name = $element->getName();

					if ($data && isset($data[$name])) {
						$element->value($data[$name]);
					}
					if (isset($_POST[$name])) {
						$element->value($_POST[$name]);
					}
				}
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

	/**
	 * @param string $name
	 * @param string $label
	 * @return Input
	 */
	public function input($name, $label = null) {
		$element = new Input($name);
		return $this->add($element, $label);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return Password
	 */
	public function password($name, $label = null) {
		$element = new Password($name);
		return $this->add($element, $label);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return Email
	 */
	public function email($name = null, $label = null) {
		if (empty($name)) {
			$name = 'email';
		}
		$element = new Input($name);
		$element->type('email');
		return $this->add($element, $label);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return Textarea
	 */
	public function textarea($name, $label = null) {
		$element = new Textarea($name);
		return $this->add($element, $label);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return File
	 */
	public function file($name, $label = null) {
		$element = new File($name);
		return $this->add($element, $label);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return File
	 */
	public function upload($name, $label = null) {
		$element = new Upload($name);
		return $this->add($element, $label);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return Select
	 */
	public function select($name, $label = null) {
		$element = new Select($name);
		return $this->add($element, $label);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return Checkbox
	 */
	public function checkbox($name, $label = null) {
		$element = new Checkbox($name);
		return $this->add($element, $label);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return Radio
	 */
	public function radio($name, $label = null) {
		$element = new Radio($name);
		return $this->add($element, $label);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return Multicheckbox
	 */
	public function multicheckbox($name, $label = null) {
		$element = new Multicheckbox($name);
		return $this->add($element, $label);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return Button
	 */
	public function button($label, $class = null) {
		$element = new Button();
		if ($class) {
			$element->class('btn-' . $class);
		}
		return $this->add($element, $label);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return Address
	 */
	public function address($name = null, $label = null) {
		if (empty($name)) {
			$name = 'address';
		} else {
			$name = 'address_' . $name;
		}
		$element = new Address($name);
		return $this->add($element, $label);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return Button
	 */
	public function submit($label = 'Submit') {
		$element = new Button();
		$element->type('submit');
		$element->label($label);
		return $this->add($element);
	}

	protected function executeGroupCallback(Group $el, $closure = null) {
		$el = $this->add($el);
		array_push($this->groups, $el);
		if ($closure === true) {
			$el->autoclose(true);
			$closure = null;
		}
		if ($closure === null) {
			return $el;
		}
		if (is_callable($closure)) {
			$closure = $closure->bindTo($el, $el);
			$closure();
		}
		array_pop($this->groups);
		return $el;
	}

	/**
	 * Close any number of opened groups
	 * @param int $i
	 * @return \k\form
	 */
	public function close($i = 1) {
		if (empty($this->groups)) {
			return $this;
		}
		$autoclose = true;
		while ($autoclose) {
			$last = end($this->groups);
			if ($last && $last->autoclose()) {
				array_pop($this->groups);
			} else {
				$autoclose = false;
			}
		}
		while ($i--) {
			array_pop($this->groups);
		}
		return $this;
	}

	/**
	 * @param string $legend
	 * @param string $closure
	 * @return Fieldset
	 */
	public function fieldset($legend = null, $closure = null) {
		$element = new Fieldset();

		if (is_array($legend)) {
			$element->setAttributes($legend);
		} elseif ($legend) {
			$element->legend($legend);
		}
		return $this->executeGroupCallback($element, $closure);
	}

	/**
	 * @param string $element
	 * @param string $label
	 * @return Element
	 */
	public function add($element, $label = null) {
		if (is_string($element)) {
			$element = new Element($element);
		}
		$element->setForm($this);
		if ($label) {
			$element->label($label);
		}
		//if we have groups and we add a new group, check for autoclose
		if (!empty($this->groups) && $element instanceof Group) {
			$last = end($this->groups);
			if ($last->autoclose()) {
				array_pop($this->groups);
			}
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
		if ($element instanceof Input) {
			if ($this->getWrap()) {
				$html .= '<div class="control-group">';
			}
			$html .= $element->renderElement();
			if ($this->getWrap()) {
				$html .= '</div>';
			}
		} elseif ($element instanceof Group) {
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

	protected function getScript() {
		
	}

	protected function renderTag($tag, $atts = [], $close = false) {
		$html = '<' . $tag;
		$atts = array_filter($atts);
		foreach ($atts as $k => $v) {
			$atts[$k] = $k . '="' . $v . '"';
		}
		$html .= ' ' . implode(' ', array_values($atts));
		if ($close) {
			$html .= '/';
		}
		$html .= '>';
		return $html;
	}

	public function renderHtml() {
		if ($this->layout) {
			$this->addClass('form-' . $this->layout);
		}
		
		$atts = array(
			'action' => $this->action,
			'method' => $this->method,
			'class' => $class,
			'id' => $this->getId()
		);
		$atts = array_merge($atts, $this->attributes);
		$html = $this->renderTag('form', $atts);
		foreach ($this->elements as $element) {
			$html .= $this->renderElement($element);
		}
		return $html;
	}
}

