<?php

namespace k;

use \InvalidArgumentException;

class Obj {

	/**
	 * Get the class name without namespace
	 * 
	 * @param string|object $obj
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public static function getClassName($obj) {
		if (is_object($obj)) {
			$obj = get_class($obj);
		}
		if (!is_string($obj)) {
			throw new InvalidArgumentException($obj);
		}
		$obj = explode('\\', $obj);
		return end($obj);
	}

	/**
	 * Get all classes in a file
	 * 
	 * @link http://stackoverflow.com/questions/928928/determining-what-classes-are-defined-in-a-php-class-file
	 * @param string $filename
	 * @return array
	 */
	protected static function getClassesInFile($filename) {
		$content = file_get_contents($filename);
		$classes = array();
		$tokens = token_get_all($content);
		$count = count($tokens);
		$buildNs = false;
		for ($i = 2; $i < $count; $i++) {
			if ($tokens[$i][0] == T_NAMESPACE) {
				$ns = '';
				$buildNs = true;
			}
			if ($buildNs) {
				if ($tokens[$i][0] == ';') {
					$buildNs = false;
				} else if ($tokens[$i][0] == T_NS_SEPARATOR || $tokens[$i][0] == T_STRING) {
					$ns .= $tokens[$i][1];
				}
			}
			if ($tokens[$i - 2][0] == T_CLASS && $tokens[$i - 1][0] == T_WHITESPACE && $tokens[$i][0] == T_STRING) {
				$className = $tokens[$i][1];
				if ($ns) {
					$className = '\\' . $ns . '\\' . $className;
				}
				$classes[] = $className;
			}
		}
		return $classes;
	}

	/**
	 * Convert to an object
	 * 
	 * @param mixed $var
	 * @return object
	 * @throws InvalidArgumentException
	 */
	public static function make($var) {
		if (is_object($var)) {
			$var = get_class($var);
		}
		if (class_exists($var)) {
			$var = new $var;
		} else {
			throw new InvalidArgumentException($var . ' can not be converted to a class');
		}
		return $var;
	}

	/**
	 * Get class without namespace
	 * @return string
	 */
	public function getClass() {
		return self::getClassName(get_called_class());
	}

	/**
	 * If an object extend  this, it's useful to get the class name when echoing 
	 * the class as a default behaviour
	 * 
	 * @return string
	 */
	public function __toString() {
		return $this->getClass();
	}

}