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
 * 
 * @link http://anahkiasen.github.io/former/
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

	//TODO: rewrite the html generation part to be part of html writer and not static

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
		if ($this->id === null) {
			$name = str_replace('/', '-', strtolower(trim($_SERVER['PATH_INFO'], '/')));
			$this->id = 'form-' . $name;
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
	
	public function find($name, $el = null) {
		if($el === null) {
			$el = $this->elements;
		}
		if (is_array($el)) {
			foreach ($el as $element) {
				if ($element instanceof \k\html\form\Group) {
					$ret = $this->find($name, $element->getElements());
					if($ret) {
						return $ret;
					}
				} else {
					if (!method_exists($element, 'getName')) {
						continue;
					}
					if($name == $element->getName()) {
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
		
		//model integration
		if(is_object($data) && $data instanceof \k\db\Orm) {
			//populate has one in fields
			$rels = $data->getHasOneRelations();
			foreach($rels as $rel => $class) {
				$field = $class::getForForeignKey($rel);
				$f = $this->find($field);
				if($f) {
					$o = $data->$rel();
					$v = $o->getId();
					$f->value($v);
					$f->attribute('data-label',$o->getLabel());
				}
			}
			//automatically bind validation rules
			$validations = $data::getValidation();
			foreach($validations as $field => $rules) {
				$f = $this->find($field);
				if($f) {
					foreach($rules as $name => $v) {
						$f->attribute('data-' . $name, $v);
					}
				}
			}
		}
		
		if (is_array($el)) {
			foreach ($el as $element) {
				if ($element instanceof \k\html\form\Group) {
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
	 * @return \k\html\form\Input
	 */
	public function input($name, $label = null) {
		$element = new \k\html\form\Input($name);
		return $this->add($element, $label);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return \k\html\form\Password
	 */
	public function password($name, $label = null) {
		$element = new \k\html\form\Password($name);
		return $this->add($element, $label);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return \k\html\form\Email
	 */
	public function email($name = null, $label = null) {
		if (empty($name)) {
			$name = 'email';
		}
		$element = new \k\html\form\Input($name);
		$element->type('email');
		return $this->add($element, $label);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return \k\html\form\Textarea
	 */
	public function textarea($name, $label = null) {
		$element = new \k\html\form\Textarea($name);
		return $this->add($element, $label);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return \k\html\form\File
	 */
	public function file($name, $label = null) {
		$element = new \k\html\form\File($name);
		return $this->add($element, $label);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return \k\html\form\File
	 */
	public function upload($name, $label = null) {
		$element = new \k\html\form\Upload($name);
		return $this->add($element, $label);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return \k\html\form\Select
	 */
	public function select($name, $label = null) {
		$element = new \k\html\form\Select($name);
		return $this->add($element, $label);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return \k\html\form\Checkbox
	 */
	public function checkbox($name, $label = null) {
		$element = new \k\html\form\Checkbox($name);
		return $this->add($element, $label);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return \k\html\form\Radio
	 */
	public function radio($name, $label = null) {
		$element = new \k\html\form\Radio($name);
		return $this->add($element, $label);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return \k\html\form\Multicheckbox
	 */
	public function multicheckbox($name, $label = null) {
		$element = new \k\html\form\Multicheckbox($name);
		return $this->add($element, $label);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return \k\html\form\Button
	 */
	public function button($label, $class = null) {
		$element = new \k\html\form\Button();
		if ($class) {
			$element->class('btn-' . $class);
		}
		return $this->add($element, $label);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return \k\html\form\Address
	 */
	public function address($name = null, $label = null) {
		if (empty($name)) {
			$name = 'address';
		} else {
			$name = 'address_' . $name;
		}
		$element = new \k\html\form\Address($name);
		return $this->add($element, $label);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return \k\html\form\Button
	 */
	public function submit($label = 'Submit') {
		$element = new \k\html\form\Button();
		$element->type('submit');
		$element->label($label);
		return $this->add($element);
	}

	protected function executeGroupCallback(\k\html\form\Group $el, $closure = null) {
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
	 * @return \k\html\Form
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
	 * @param string $class
	 * @param string $closure
	 * @return \k\html\form\Div
	 */
	public function div($class = null,$id = null,$closure = null) {
		$element = new \k\html\form\Div();
		if (is_array($class)) {
			$element->setAttributes($class);
		} elseif ($class) {
			$element->class($class);
		}
		
		if (is_array($id)) {
			$element->setAttributes($id);
		} elseif ($id) {
			$element->id($id);
		}
		
		return $this->executeGroupCallback($element, $closure);
	}

	/**
	 * @param string $legend
	 * @param string $closure
	 * @return \k\html\form\Fieldset
	 */
	public function fieldset($legend = null, $closure = null) {
		$element = new \k\html\form\Fieldset();

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
	 * @return \k\html\form\Element
	 */
	public function add($element, $label = null) {
		if (is_string($element)) {
			$element = new \k\html\form\Element($element);
		}
		$element->setForm($this);
		if ($label) {
			$element->label($label);
		}
		//if we have groups and we add a new group, check for autoclose
		if (!empty($this->groups) && $element instanceof \k\html\form\Group) {
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
		$atts = array(
			'action' => $this->action,
			'method' => $this->method,
			'class' => $class,
			'enctype' => $enctype,
			'id' => $this->getId()
		);
		$atts = array_merge($atts, $this->attributes);
		$html = self::makeTag('form', $atts);
		foreach ($this->elements as $element) {
			$html .= $this->renderElement($element);
		}

		return $html;
	}

}

