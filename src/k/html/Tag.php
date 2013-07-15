<?php

namespace k\html;

/**
 * Tag
 *
 * @author lekoala
 */
abstract class Tag extends HtmlWriter {
	
	protected $tagName;
	protected $class = [];
	protected $attributes = [];
	
	public function openTag() {
		$html = "<{$this->tagName}";
		if(!empty($this->attributes)) {
			$html .= $this->renderAttributes();
		}
		return $html;
	}
	
	public function closeTag() {
		return "</{$this->tagName}>";
	}
	
	public function data($k,$v = null) {
		if($v === null) {
			return $this->getData($k);
		}
		return $this->setData($k, $v);
	}
	
	public function getData($k) {
		return $this->getAttribute('data-' . $k);
	}
	
	public function setData($k,$v) {
		return $this->setAttribute('data-' . $k, $v);
	}
	
	public function att($k,$v = null) {
		if($v === null) {
			return $this->getAttribute($k);
		}
		return $this->setAttribute($k, $v);
	}
		
	protected function renderAttributes($close = false) {
		$html = '';
		$atts = array_filter($this->attributes);
		foreach($atts as $k => $v) {
			if(is_array($v)) {
				$v = implode(' ', $v);
			}
			$atts[$k] = $k . '="' . $v . '"';
		}
		$html .= ' ' . implode(' ', array_values($atts));
		if($close) {
			$html .= '/';
		}
		$html .= '>';
		return $html;
	}
	
	public function getAttribute($k) {
		return isset($this->attributes[$k]) ? $this->attributes[$k] : null;
	}
	
	public function setAttribute($k,$v) {
		$this->attributes[$k] = $v;
		return $this;
	}
	
	public function removeAttribute($k) {
		if(isset($this->attributes[$k])) {
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
	
	public function hasClass($k) {
		return in_array($k,$this->class);
	}
	
	public function setClass($v) {
		if(!is_array($v)) {
			$v = [$v];
		}
		$this->class = $v;
		return $this;
	}
	
	public function addClass($v) {
		if(!is_array($v)) {
			$v = [$v];
		}
		$this->class = array_merge($this->class,$v);
		return $this;
	}
	
	public function removeClass($v) {
		if(!is_array($v)) {
			$v = [$v];
		}
		$this->class = array_diff($this->class, $v);
	}
}