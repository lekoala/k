<?php

namespace k\form;

class Select extends Input {

	protected $tagName = 'select';
	protected $options = [];

	public function addOption($k = '', $v = null) {
		if (!$v) {
			$v = $k;
		}
		$this->options[$k] = $v;
		return $this;
	}

	public function getOptions() {
		return $this->options;
	}

	public function getName($groups = true) {
		$name = parent::getName($groups);
		if ($this->getAttribute('multiple')) {
			return $name . '[]';
		}
		return $name;
	}

	/**
	 * Set the select to be multiple
	 * Automatically set if you set the value to an array
	 * 
	 * @param bool $multiple
	 * @return \k\form\Select
	 */
	public function multiple($multiple = 'multiple') {
		$this->setAttribute('multiple',$multiple);
		return $this;
	}

	/**
	 * Set options of the select element
	 * 
	 * @param string|array $options Path to a file (php or csv) or array
	 * @param bool|string $first First element
	 * @return \k\form\Select
	 * @throws Exception
	 */
	public function options($options, $first = false) {
		if (is_string($options)) {
			$ext = pathinfo($options, PATHINFO_EXTENSION);
			if ($ext) {
				switch ($ext) {
					case 'php':
						$options = require $options;
						break;
					case 'csv':
						$arr = array();
						if (($handle = fopen($options, "r")) !== FALSE) {
							while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
								$arr[$data[0]] = $data[1];
							}
							fclose($handle);
						}
						$options = $arr;
						break;
					default:
						throw new Exception('Unsupported file type : ' . $ext);
						break;
				}
			} else {
				$options = explode(',', $options);
			}
		}
		if ($first) {
			if ($first === true) {
				$first = '';
			}
			$options = array('' => $first) + $options;
		}
		$this->options = $options;
		return $this;
	}

	protected function renderOptions() {
		$html = '';
		$currentValue = $this->getValue();
		foreach ($this->options as $value => $label) {
			$selected = 0;
			if ($this->getAttribute('multiple')) {
				if ($currentValue && in_array($value, $currentValue)) {
					$selected = 1;
				}
			} else {
				if ($value == $currentValue) {
					$selected = 1;
				}
			}
			$html .= '<option ' . $this->_renderAttributes(compact('value','selected')) . '>' . $label . "</option>\n";
		}
		return $html;
	}

	public function openTag($close = false) {
		$this->removeAttribute('type');
		$attributes = $this->getAttributes();
		if(isset($attributes['value'])) {
			unset($attributes['value']);
		}
		return '<select ' . $this->_renderAttributes($attributes) . '>';
	}

	public function renderContent() {
		if (is_array($this->getValue())) {
			$this->setAttribute('multiple', true);
		}
		
		$html = $this->openTag();
		$html .= $this->renderOptions();
		$html .= $this->closeTag();
		return $html;
	}

}