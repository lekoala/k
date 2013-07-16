<?php

namespace k\form;

use \Closure;
use \ArrayAccess;

/**
 * Form builder
 * 
 * Use twitter bootstrap html
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
 * @link http://twitter.github.io/bootstrap/base-css.html#forms
 * @link http://anahkiasen.github.io/former/
 * @link https://github.com/zendframework/zf2/blob/master/library/Zend/Form/Form.php
 */
class Form extends Element implements ArrayAccess {

	const FORM_SEARCH = 'search';
	const FORM_INLINE = 'inline';
	const FORM_HORIZONTAL = 'horizontal';
	const ENCTYPE_MULTIPART_FORM_DATA = 'multipart/form-data';

	protected $tagName = 'form';
	protected $attributes = [
		'action' => '',
		'method' => 'POST'
	];
	protected $actions = [];
	protected $elements = [];
	protected $legend;
	protected $form; //for subforms
	protected $wrap = true;
	protected $layout = 'horizontal';
	protected $translations;
	protected $rules = [];
	protected $validator;
	protected $showErrors = false;
	protected static $defaultClass = 'form';
	protected static $largeOptionsLimit = 20;

	public function __construct(Form $form = null) {
		parent::__construct($form);
		$this->init();
		$this->handleActions();
	}

	protected function init() {
		
	}
	
	protected function getDataFromMethod() {
		switch($this->getAttribute('method')) {
			case 'GET':
				return $_GET;
				break;
			default:
				return $_POST;
		}
	}
	
	
	public function getValidator() {
		if($this->validator === null) {
			$this->validator = new \k\Validator($this->getDataFromMethod(),$this->rules);
		}
		return $this->validator;
	}
	
	public function isValid() {
		if($this->getValidator()->validate()) {
			return true;
		}
		return false;
	}
	
	public function errors() {
		return $this->getValidator()->errors();
	}

	public function handleActions() {
		$data = $this->getDataFromMethod();
		foreach ($this->actions as $name => $button) {
			if (isset($data[$button->getName()])) {
				$method = 'on' . ucfirst($name);
				if($this->isValid()) {
					$this->$method();
				}
				else {
					$this->showErrors = true;
				}
			}
		}
	}

	public function getTranslations() {
		return $this->translations;
	}

	public function setTranslations($value) {
		$this->translations = $value;
		return $this;
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
		return $this->getAttribute('enctype');
	}

	public function setEnctype($v = 'multipart/form-data') {
		return $this->setAttribute('enctype', $v);
	}

	public function find($name, $elements = null) {
		if ($elements === null) {
			$elements = $this->elements;
		}
		foreach ($elements as $element) {
			//recursive for groups
			if ($element instanceof Form) {
				$ret = $this->find($name, $element->getElements());
				if ($ret) {
					return $ret;
				}
			}
			if (!method_exists($element, 'getName')) {
				continue;
			}
			if ($name == $element->getName()) {
				return $element;
			}
		}
		return false;
	}

	public function findForm($name, $elements = null) {
		if ($elements === null) {
			$elements = $this->elements;
		}
		foreach ($this->elements as $element) {
			if ($element instanceof Form) {
				if ($element->getName($name)) {
					return $element;
				}
				$ret = $this->findForm($name, $element->getElements());
				if ($ret) {
					return $ret;
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
			foreach ($el as $name => $element) {
				if ($element instanceof Form) {
					$this->populate($data, $element->getElements());
				} else {
					$name = $element->getName();

					$dataVal = $this->getValueFromArray($data, $name);
					$postVal = $this->getValueFromArray($_POST, $name);

					if ($dataVal) {
						$element->setValue($dataVal);
					}
					if ($postVal) {
						$element->setValue($postVal);
					}
				}
			}
		}

		return $this;
	}

	protected function getValueFromArray($array, $index, $default = null) {
		$loc = &$array;
		foreach (explode('[', $index) as $step) {
			$step = rtrim($step, ']');
			if (isset($loc[$step])) {
				$loc = &$loc[$step];
			} else {
				return $default;
			}
		}
		return $loc;
	}

	public function dumpTranslationsArray() {
		$translations = array();
		foreach ($this->elements as $element) {
			if (!method_exists($element, 'getName')) {
				continue;
			}
			$name = $element->getName();
			$translations[$name] = $name;
			if (method_exists($element, 'getOptions')) {
				$opts = $element->getOptions();
				//skip large arr
				if (count($opts) > self::$largeOptionsLimit) {
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
		$element = new Input();
		$element->setName($name);
		$element->setLabel($label);
		return $this->addElement($element);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return Password
	 */
	public function password($name, $label = null) {
		$element = new Password();
		$element->setName($name);
		$element->setLabel($label);
		return $this->addElement($element);
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
		$element = new Input();
		$element->setName($name);
		$element->setLabel($label);
		$element->setType('email');
		return $this->addElement($element);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return Textarea
	 */
	public function textarea($name, $label = null) {
		$element = new Textarea();
		$element->setName($name);
		$element->setLabel($label);
		return $this->addElement($element);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return File
	 */
	public function file($name, $label = null) {
		$element = new File();
		$element->setName($name);
		$element->setLabel($label);
		return $this->addElement($element);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return File
	 */
	public function upload($name, $label = null) {
		$element = new Upload();
		$element->setName($name);
		$element->setLabel($label);
		return $this->addElement($element);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return Select
	 */
	public function select($name, $label = null) {
		$element = new Select();
		$element->setName($name);
		$element->setLabel($label);
		return $this->addElement($element);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return Checkbox
	 */
	public function checkbox($name, $label = null) {
		$element = new Checkbox();
		$element->setName($name);
		$element->setLabel($label);
		return $this->addElement($element);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return Radio
	 */
	public function radio($name, $label = null) {
		$element = new Radio($name);
		$element->setName($name);
		$element->setLabel($label);
		return $this->addElement($element);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return Multicheckbox
	 */
	public function multicheckbox($name, $label = null) {
		$element = new Multicheckbox($name);
		$element->setName($name);
		$element->setLabel($label);
		return $this->addElement($element);
	}

	/**
	 * @param string $name
	 * @param string $content
	 * @return Button
	 */
	public function button($content, $class = null) {
		$element = new Button();
		if ($class) {
			$element->addClass('btn-' . $class);
		}
		$element->setContent($content);
		return $this->addAction($element, $content);
	}

	/**
	 * @param string $content
	 * @param string $name
	 * @return Button
	 */
	public function submit($content = 'Submit', $name = null) {
		if ($name === null) {
			//the first action can match the id
			if (empty($this->actions)) {
				$name = 'submit';
			} else {
				$name = str_replace(' ', '', ucwords($content));
			}
		}
		$element = new Button();
		$element->setName('submit::' . $this->getId() . '::' . $name);
		$element->setType('submit');
		$element->setContent($content);
		return $this->addAction($element, $name);
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
		return $this->addElement($element);
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
			$element->setLegend($legend);
		}
		return $this->executeGroupCallback($element, $closure);
	}

	/**
	 * @param string $element
	 * @param string $name
	 * @return Element
	 */
	public function addElement($element, $name = null) {
		if (is_string($element)) {
			$element = new Element();
			$element->setContent($element);
		}
		if ($name) {
			$element->setName($name);
		} else {
			$name = $element->getName();
		}
		$element->setForm($this);
		$this->elements[$name] = $element;
		return $element;
	}

	public function hasElement($element) {
		if (!is_string($element)) {
			$element = $element->getName();
		}
		if (empty($element)) {
			return false;
		}
		return isset($this->elements[$element]);
	}

	public function removeElement($element) {
		if (!is_string($element)) {
			$element = $element->getName();
		}
		if ($this->hasElement($element)) {
			unset($this->elements[$element]);
		}
	}

	public function getElement($element) {
		return isset($this->elements[$element]) ? $this->elements[$element] : null;
	}

	public function addAction($element, $name = null) {
		if (is_string($element)) {
			$element = new Button($element);
			$element->setName($name);
		}
		if ($name) {
			$element->setName($name);
		} else {
			$name = $element->getName();
		}
		$element->setForm($this);
		$this->actions[$name] = $element;
		return $element;
	}

	public function hasAction($action) {
		if (!is_string($action)) {
			$action = $action->getName();
		}
		if (empty($action)) {
			return false;
		}
		return isset($this->elements[$action]);
	}

	public function removeAction($action) {
		if (!is_string($action)) {
			$action = $action->getName();
		}
		if ($this->hasElement($action)) {
			unset($this->actions[$action]);
		}
	}

	public function getElements() {
		return $this->elements;
	}

	public function renderActions() {
		if (empty($this->actions)) {
			$this->submit();
		}
		$html = '';
		foreach ($this->actions as $name => $action) {
			$html .= $action->render() . "\n";
		}
		return $html;
	}

	public function openTag($close = false) {
		if (self::$defaultClass) {
			$this->addClass(self::$defaultClass);
		}
		if ($this->layout) {
			$this->addClass('form-' . $this->layout);
		}
		return parent::openTag($close);
	}

	public function renderLegend() {
		if (!$this->legend) {
			return '';
		}
		return '<legend>' . $this->legend . '</legend>';
	}
	
	public function renderErrors($inline = false) {
		$html = '<div class="alert alert-error"><ul>';
		foreach($this->errors() as $err) {
		$this->find($err['name'])->addClass('error')->help($err['message'],true);
			$html .= '<li>' . $err['message'] . '</li>';
		}
		$html .= '</ul></div>';
		if($inline) {
			return '';
		}
		return $html;
	}

	public function renderHtml() {
		$html = $this->openTag();
		
		//errors
		if($this->showErrors) {
			$html .= $this->renderErrors(true);
		}
		//legend
		$html .= $this->renderLegend();
		//elements
		foreach ($this->elements as $name => $element) {
			$html .= $element->render() . "\n";
		}
		//actions
		if (empty($this->actions)) {
			$this->submit();
		}
		$html .= '<div class="form-actions">';
		$html .= $this->renderActions();
		$html .= '</div>';
		$html .= $this->closeTag();
		return $html;
	}

	public function __get($name) {
		return $this->getElement($name);
	}

	/* --- ArrayAccess --- */

	public function offsetSet($offset, $value) {
		return $this->addElement($offset, $value);
	}

	public function offsetExists($offset) {
		return $this->hasElement($offset);
	}

	public function offsetUnset($offset) {
		return $this->removeElement($offset, null);
	}

	public function offsetGet($offset) {
		return $this->getElement($offset);
	}

}

