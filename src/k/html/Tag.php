<?php

namespace k\html;

/**
 * Tag
 *
 * @author lekoala
 */
class Tag extends HtmlWriter {

	protected $tagName;
	protected $attributes = [];
	protected $content;

	public function getTagName() {
		return $this->tagName;
	}

	public function setTagName($v) {
		$this->tagName = $v;
		return $this;
	}

	public function openTag($close = false) {
		$html = "<{$this->tagName}";
		if (!empty($this->attributes)) {
			$html .= ' ' . $this->renderAttributes();
		}
		if ($close) {
			$html .= '/';
		}
		$html .= '>';
		return $html;
	}

	public function closeTag() {
		return "</{$this->tagName}>";
	}

	public function renderTag($content = null) {
		return $this->openTag() . $content . $this->closeTag();
	}

	public function getId() {
		static $count = 0;
		if (!$this->hasAttribute('id')) {
			$name = str_replace('/', '-', strtolower(trim(isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : null, '/')));
			if (++$count > 1 || empty($name)) {
				$name .= '-' . $count;
			}
			if (empty($name)) {
				$name = '-' . $name;
			}
			$this->setId($this->tagName . $name);
		}
		return $this->getAttribute('id');
	}

	public function setId($v) {
		return $this->setAttribute('id', $v);
	}

	public function data($k, $v = null) {
		if ($v === null) {
			return $this->getData($k);
		}
		return $this->setData($k, $v);
	}

	public function getData($k) {
		return $this->getAttribute('data-' . $k);
	}

	public function setData($k, $v) {
		return $this->setAttribute('data-' . $k, $v);
	}

	public function att($k, $v = null) {
		if ($v === null) {
			return $this->getAttribute($k);
		}
		return $this->setAttribute($k, $v);
	}

	public function renderHtml() {
		return $this->renderTag($this->content);
	}

	protected function renderAttributes() {
		return $this->_renderAttributes($this->attributes);
	}

	protected function _renderAttributes($attributes) {
		$html = '';
		$atts = array_filter($attributes);
		foreach ($atts as $k => $v) {
			if (empty($v)) {
				continue;
			}
			if (is_array($v)) {
				$glue = ';';
				if (in_array($k, ['class'])) {
					$glue = ' ';
				}
				$v = implode($glue, $v);
			}
			if ($k == 'selected' || $k == 'checked' || $k == 'multiple') {
				if ($v) {
					$v = $k;
				}
			}
			$atts[$k] = $k . '="' . $v . '"';
		}
		return implode(' ', array_values($atts));
	}

	public function hasAttribute($k) {
		return isset($this->attributes[$k]);
	}

	public function getAttribute($k, $default = null) {
		if (isset($this->attributes[$k])) {
			return $this->attributes[$k];
		}
		return $default;
	}

	public function setAttribute($k, $v) {
		$this->attributes[$k] = $v;
		return $this;
	}
	
	public function removeAttribute($k) {
		if (isset($this->attributes[$k])) {
			unset($this->attributes[$k]);
		}
	}

	public function getAttributes() {
		return $this->attributes;
	}

	public function setAttributes($v) {
		$this->attributes = $v;
		return $this;
	}
	
	public function addAttributes($v) {
		$this->attributes = array_merge($this->attributes,$v);
	}
	
	public function removeAttributes($v) {
		foreach($v as $k) {
			$this->removeAttribute($k);
		}
	}

	public function hasClass($k = null) {
		if ($k === null) {
			return $this->hasAttribute('class');
		}
		return in_array($k, $this->getClass('class'));
	}

	public function getClass($arr = false) {
		$class = $this->getAttribute('class', []);
		if ($arr) {
			return $class;
		}
		return implode(' ', $class);
	}

	public function setClass($v) {
		if (!is_array($v)) {
			$v = [$v];
		}
		return $this->setAttribute('class', $v);
	}

	public function addClass($v) {
		if (!is_array($v)) {
			$v = [$v];
		}
		return $this->setAttribute('class', array_unique(array_merge($this->getClass(true), $v)));
	}

	public function removeClass($v) {
		if (!is_array($v)) {
			$v = [$v];
		}
		return $this->setAttribute('class', array_diff($this->getClass(true), $v));
	}

	public function hasStyle($k = null) {
		if ($k == null) {
			return $this->hasAttribute('style');
		}
		return in_array($k, $this->getAttribute('style'));
	}

	public function getStyle($arr = false) {
		$Style = $this->getAttribute('style', []);
		if ($arr) {
			return $Style;
		}
		return implode(';', $Style);
	}

	public function setStyle($v) {
		if (!is_array($v)) {
			$v = [$v];
		}
		return $this->setAttribute('style', $v);
	}

	public function addStyle($v) {
		if (!is_array($v)) {
			$v = [$v];
		}
		return $this->setAttribute('style', array_merge($this->getStyle(true), $v));
	}

	public function removeStyle($v) {
		if (!is_array($v)) {
			$v = [$v];
		}
		return $this->setAttribute('style', array_diff($this->getStyle(true), $v));
	}

}