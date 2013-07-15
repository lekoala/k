<?php

namespace k\html;

use \Exception;

/**
 * HtmlWriter
 *
 * @author lekoala
 */
abstract class HtmlWriter {

	abstract function renderHtml();

	protected function getScript() {
		
	}

	/**
	 * Return the html content
	 */
	public function render($format = false) {
		$this->html = $this->renderHtml();
		if ($format) {
			$this->html = $this->formatXml($this->html);
		}
		if ($this->getScript()) {
			$this->html .= $this->getScript();
		}
		return $this->html;
	}

	public function __toString() {
		try {
			return $this->render();
		} catch (\Exception $e) {
			return $e->getMessage() . ' in file ' . $e->getFile() . ' at line ' . $e->getLine();
		}
	}

	/**
	 * Format an xml string
	 * 
	 * @param string $xml
	 * @param int $spaces
	 * @param bool $escape
	 * @return string
	 */
	protected function formatXml($xml, $spaces = 4, $escape = false) {

		$xml = preg_replace('/(>)(<)(\/*)/', "$1\n$2$3", $xml);

		$token = strtok($xml, "\n");
		$result = '';
		$pad = 0;
		$matches = array();

		while ($token !== false) :

			// 1. open and closing tags on same line - no change
			if (preg_match('/.+<\/\w[^>]*>$/', $token, $matches)) :
				$indent = 0;
			// 2. closing tag - outdent now
			elseif (preg_match('/^<\/\w/', $token, $matches)) :
				$pad -= $spaces;
			// 3. opening tag - don't pad this one, only subsequent tags
			elseif (preg_match('/^<\w[^>]*[^\/]>.*$/', $token, $matches)) :
				$indent = 1;
			// 4. no indentation needed
			else :
				$indent = 0;
			endif;

			// pad the line with the required number of leading spaces
			$line = str_pad($token, strlen($token) + $pad, ' ', STR_PAD_LEFT);
			$result .= $line . "\n";
			$token = strtok("\n");
			$pad += $indent * $spaces;
		endwhile;

		$result = rtrim($result, "\n");

		if ($escape) {
			$result = htmlentities($result, ENT_QUOTES, "UTF-8");
		}

		return $result;
	}

	/**
	 * Generate a tag
	 * 
	 * @param string|array $tag Can be an array for attributes
	 * @param string|array $value Can be an array for attributes
	 * @param array $attributes
	 * @return string
	 */
	protected function tag($tag, $value = null, $attributes = array()) {
		$attr = $str = '';

		//allow attr as 1nd element
		if (is_array($tag)) {
			$attributes = $tag;
			$tag = null;
		}
		//allow attr as 2nd element
		if (is_array($value)) {
			$attributes = $value;
			$value = null;
		}

		//default as div
		if (empty($tag)) {
			$tag = 'div';
			if (isset($attributes['tag'])) {
				$tag = $attributes['tag'];
			}
		}
		if ($tag === 'textarea' && isset($attributes['value'])) {
			$value = $attributes['value'];
			unset($attributes['value']);
		}

		//comments support
		if (isset($attributes['comments'])) {
			$str = $attributes['comments'];
			unset($attributes['comments']);
		}

		//value as text key
		if (isset($attributes['text'])) {
			$value = $attributes['text'];
			unset($attributes['text']);
		}

		//prepare html
		foreach ($attributes as $k => $v) {
			if (empty($v)) {
				continue;
			}
			if (is_int($k)) {
				$k = $v;
				$v = '';
			}
			if (is_array($v)) {
				$v = implode(' ', $v);
			}
			$attr .= ' ' . $k . '="' . $v . '"';
		}
		if ($value) {
			$str .= '<' . $tag . $attr . '>' . $value . '</' . $tag . '>';
		} else {
			$str .= '<' . $tag . $attr . ' />';
		}
		$str .= "\n";
		return $str;
	}

	/* Convenient helpers */

	/**
	 * Convert a var to array
	 * 
	 * @param mixed $var
	 * @param string $delimiter (for string parameters)
	 * @param bool $trim (for string parameters)
	 * @return array 
	 */
	protected function arrayify($var, $delimiter = ',', $trim = true) {
		if (is_array($var)) {
			return $var;
		}
		if (empty($var)) {
			return array();
		}
		if (is_string($var)) {
			$array = explode($delimiter, $var);
			if ($trim) {
				array_walk($array, 'trim');
			}
			return $array;
		}
		if (is_object($var)) {
			if (method_exists($var, 'toArray')) {
				return $var->toArray();
			}
			return get_object_vars($var);
		}
		throw new Exception('Arrayify does not support objects of type ' . gettype($var));
	}

	/**
	 * Convert a var to a string
	 * 
	 * @param mixed $var
	 * @param string $glue
	 * @return string
	 */
	protected function stringify($var, $glue = ',') {
		if (empty($var)) {
			return '';
		}
		if (is_bool($var)) {
			if ($var) {
				return 'true';
			}
			return 'false';
		}
		if (is_string($var) || is_int($var) || is_float($var)) {
			return (string) $var;
		}
		if (is_array($var)) {
			$string = implode($glue, $var);
			return $string;
		}
		if (is_object($var)) {
			if (method_exists($var, '__toString')) {
				return (string) $var;
			}
			throw new Exception('Object does not have a __toString method');
		}
		throw new Exception('Stringify does not support objects of type ' . gettype($var));
	}

	/**
	 * Collapse an array
	 * 
	 * @param array
	 * @return array
	 */
	protected function arrayCollapse(array $arr) {
		$a = array();
		foreach ($arr as $k => $v) {
			if (is_int($k)) {
				$k = $v;
			}
			$a[] = $k;
		}
		return $a;
	}

	/**
	 * Strecht an array
	 * 
	 * @param array
	 * @return array
	 */
	protected function arrayStretch(array $arr) {
		$a = array();
		foreach ($arr as $k => $v) {
			if (is_int($k) && is_string($v)) {
				$k = $v;
			}
			$a[$k] = $v;
		}
		return $a;
	}

}