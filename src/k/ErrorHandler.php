<?php

namespace k;

use ErrorException;
use Exception;

/**
 * ErrorHandler
 *
 * @author lekoala
 */
class ErrorHandler {
	
	protected $enabled = true;
	
	public function __construct($log = null) {
		if ($log) {
			ini_set('error_log', $log);
		}
		set_error_handler(array($this, 'throwError'));
		register_shutdown_function(array($this, 'onError'));
	}
	
	public function getEnabled() {
		return $this->enabled;
	}

	public function setEnabled($enabled = true) {
		$this->enabled = $enabled;
		return $this;
	}

	public function throwError($code, $message, $file, $line) {
		if ((error_reporting() & $code) !== 0) {
			throw new ErrorException($message, $code, 0, $file, $line);
		}
		return true;
	}

	public function onError($e = null) {
		if(!$this->enabled) {
			return;
		}
		if ($e === null) {
			$err = error_get_last();
			if ($err) {
				$e = new ErrorException($err['message'], 0, $err['type'], $err['file'], $err['line']);
			}
		}
		if ($e) {
			if (!class_exists('App', false) || App::getInstance()->config('debug')) {
				exit(require dirname(__DIR__) . '/error_debug.php');
			} else {
				exit(require dirname(__DIR__) . '/error.php');
			}
		}
	}

	public function wrap($func) {
		try {
			$func();
			$app = App::getInstance();
			if($app) {
				$app->setErrorHandler($this);
			}
		} catch (\k\AppException $e) {
			switch ($e->getCode()) {
				case \k\AppException::NOT_INSTALLED:
					require dirname(__DIR__) . '/install.php';
					break;
				default:
					$this->onError($e);
					break;
			}
		} catch (Exception $e) {
			$this->onError($e);
		}
	}

	public function lowlight($php) {
		static $in_comment;

		if (preg_match('#/\*#', $php)) {
			$in_comment = true;
		}
		$php = str_replace(' ', '&nbsp;', $php);
		if ($in_comment) {
			$php = "<span class='comment'>$php</span>";
		} else {
			$php = preg_replace("#(//.*)#", "<span class='comment'>$1</span>", $php);
			$php = preg_replace("#([a-zA-Z0-9_]+)\(#", "<span class='function'>$1</span>(", $php);
			$php = preg_replace("#(->|\+|\!)#", "<span class='operator'>$1</span>", $php);
			$php = preg_replace("#(}|{|\(|\)|\[|\])#", "<span class='parenthesis'>$1</span>", $php);
			$php = preg_replace('#(\$[a-zA-Z0-9_]+)#', "<span class='var'>$1</span>", $php);
		}
		if (preg_match('#\*/#', $php)) {
			$in_comment = false;
		}
		return $php;
	}

	public function highlightCode($file, $line, $linesAround = 10) {
		$data = explode("\n", file_get_contents($file));
		$start = $line - $linesAround;
		if ($start < 0) {
			$start = 0;
		}
		$end = $line + $linesAround;
		if ($end > count($data)) {
			$end = count($data);
		}
		$html = '<table>';
		for ($i = $start; $i < $end; $i++) {
			$class = $i % 2 ? 'even' : 'odd';
			if ($i == $line - 1) {
				$class = 'current';
			}
			$html .= '<tr>';
			$html .= "<td class='$class num'><code>" . ($i + 1) . "</code></td>";
			$html .= "<td class='$class'><code>" . $this->lowlight($data[$i]) . "</code></td>";
			$html .= '</tr>';
		}
		$html .= '</table>';
		return $html;
	}

	public function formatTrace($trace = null) {
		$e = null;
		if ($trace === null) {
			$trace = debug_backtrace();
		}
		if ($trace instanceof Exception) {
			$e = $trace;
			$trace = $e->getTrace();
		}
		$html = '<code><table>';

		$i = 0;
		foreach ($trace as $tr) {
			$i++;
			$file = 'php';
			if (isset($tr['file'])) {
				$file = $tr['file'];
			}
			$line = 0;
			if (isset($tr['line'])) {
				$line = $tr['line'];
			}
			$function = isset($tr['function']) ? $tr['function'] : null;
			$class = isset($tr['class']) ? $tr['class'] : null;
			$object = isset($tr['object']) ? $tr['object'] : null;
			$type = isset($tr['type']) ? $tr['type'] : null;
			$args = isset($tr['args']) ? $tr['args'] : array();
			$args_types = array();

			$ct = function($arg) use(&$ct) {
				switch (gettype($arg)) {
					case 'integer':
					case 'double':
						return '<span style="color:red">' . $arg . '</span>';
					case 'string':
						$arg = htmlspecialchars(substr($arg, 0, 64)) . ((strlen($arg) > 64) ? '...' : '');
						return '<span style="color:green">"' . $arg . '"</span>';
					case 'array':
						$tmp = array();
						foreach ($arg as $arg) {
							$tmp[] = $ct($arg);
						}
						return 'Array(' . implode(',', $tmp) . ')';
					case 'object':
						return '<span style="color:grey">Object(<em>' . get_class($arg) . '</em>)</span>';
					case 'resource':
						return '<span style="color:orange">Resource</span>';
					case 'boolean':
						return $arg ? 'TRUE' : 'FALSE';
					case 'NULL':
						return 'NULL';
					default:
						return '?';
				}
			};

			foreach ($args as $arg) {
				$args_types[] = $ct($arg);
			}

			$cl = ($i % 2) ? 'even' : 'odd';
			$html .= "<tr><td class='$cl'>" . basename($file) . "<span class='separator'>:</span><span class='num'>" . $line . "</span></td><td class='$cl'>" . $class . $type . $function . '(' . implode(',', $args_types) . ')' . "</td></tr>";
		}
		$html .= '</table></code>';
		return $html;
	}

}