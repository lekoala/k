<?php
namespace k\form;

class Select extends Input {

	protected $options = array();

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

	public function options($options) {
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
		$this->options = array_merge($this->options, $options);
		return $this;
	}

	protected function renderOptions() {
		$html = '';
		$value = $this->getValue();
		foreach ($this->options as $k => $v) {
			if (is_int($k)) {
				$k = $v;
			}
			$selected = 0;
			if ($k == $value) {
				$selected = 1;
			}
			$this->form->t($v);
			$html .= static::makeTag('option', array(
						'value' => $k,
						'text' => $v,
						'selected' => $selected
					));
		}
		return $html;
	}

	protected function renderField() {
		$html = static::makeTag('select', array(
					'name' => $this->getName(),
					'class' => $this->class
				));
		$html .= $this->renderOptions();
		$html .= '</select>';
		return $html;
	}

}