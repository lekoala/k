<?php

namespace k\html\form;

class Select extends Input {

	protected $options = array();
	protected $multiple;

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

	/**
	 * Set the select to be multiple
	 * Automatically set if you set the value to an array
	 * 
	 * @param bool $multiple
	 * @return \k\html\form\Select
	 */
	public function multiple($multiple = 'multiple') {
		$this->multiple = $multiple;
		return $this;
	}

	/**
	 * Set options of the select element
	 * 
	 * @param string|array $options Path to a file (php or csv) or array
	 * @param bool|string $first First element
	 * @return \k\html\form\Select
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
		$value = $this->getValue();
		foreach ($this->options as $k => $v) {
			$selected = 0;
			if ($this->multiple) {
				if (in_array($k, $value)) {
					$selected = 1;
				}
			} else {
				if ($k == $value) {
					$selected = 1;
				}
			}

			$this->form->t($v);
			$tag = $this->renderHtmlTag('option', array(
				'value' => $k,
				'selected' => $selected
			));
			$html .= $tag . $v . '</option>';
		}
		return $html;
	}
	
	protected function getBaseAttributes() {
		$atts = parent::getBaseAttributes();
		$atts['multiple'] = $this->multiple;
		if(isset($atts['value'])) {
			unset($atts['value']);
		}
		unset($atts['type']);
		return $atts;
	}

	protected function renderField() {
		if (is_array($this->getValue())) {
			$this->multiple = true;
		}
		$html = $this->renderHtmlTag('select', $this->getElementAttributes());
		$html .= $this->renderOptions();
		$html .= '</select>';
		return $html;
	}

}