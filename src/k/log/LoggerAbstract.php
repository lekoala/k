<?php

namespace k\log;

use \InvalidArgumentException;

/**
 * LogAbstract
 *
 * @author lekoala
 */
abstract class LoggerAbstract implements LoggerInterface {

	const EMERGENCY = 'emergency';
	const ALERT = 'alert';
	const CRITICAL = 'critical';
	const ERROR = 'error';
	const WARNING = 'warning';
	const NOTICE = 'notice';
	const INFO = 'info';
	const DEBUG = 'debug';

	protected $enabled = true;
	protected $logs = array();
	protected $priorities = array(
		'debug',
		'info',
		'notice',
		'warning',
		'error',
		'critical',
		'alert',
		'emergency'
	);
	protected $threshold = 0;
	protected $lastTime;

	/**
	 * System is unusable.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	public function emergency($message, array $context = array()) {
		$this->log(self::EMERGENCY, $message, $context);
	}

	/**
	 * Action must be taken immediately.
	 *
	 * Example: Entire website down, database unavailable, etc. This should
	 * trigger the SMS alerts and wake you up.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	public function alert($message, array $context = array()) {
		$this->log(self::ALERT, $message, $context);
	}

	/**
	 * Critical conditions.
	 *
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	public function critical($message, array $context = array()) {
		$this->log(self::CRITICAL, $message, $context);
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	public function error($message, array $context = array()) {
		$this->log(self::ERROR, $message, $context);
	}

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things
	 * that are not necessarily wrong.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	public function warning($message, array $context = array()) {
		$this->log(self::WARNING, $message, $context);
	}

	/**
	 * Normal but significant events.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	public function notice($message, array $context = array()) {
		$this->log(self::NOTICE, $message, $context);
	}

	/**
	 * Interesting events.
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	public function info($message, array $context = array()) {
		$this->log(self::INFO, $message, $context);
	}

	/**
	 * Detailed debug information.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	public function debug($message, array $context = array()) {
		$this->log(self::DEBUG, $message, $context);
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param mixed $level
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	public function log($level, $message, array $context = array()) {
		if (!$this->enabled) {
			return;
		}

		$search = array_search($level, $this->priorities);
		if ($search === null) {
			throw new InvalidArgumentException($level);
		}

		//check threshold
		if ($this->threshold > $search) {
			return;
		}

		if (is_array($message)) {
			$message = json_encode($message);
		}
		$message = (string) $message;

		//enrich context
		$t = time();
		$this->context($context, 'time', $t);
		$this->context($context, 'datetime', date('Y-m-d H:i:s', $t));
		if ($this->lastTime) {
			$this->context($context, 'time_diff', $this->lastTime - $t);
		}
		$this->lastTime = $t;

		$message = $this->interpolate($message, $context);

		$this->logs[] = compact('level', 'message', 'context');

		$this->_log($level, $message, $context);
	}

	public function getEnabled() {
		return $this->enabled;
	}

	public function setEnabled($enabled = true) {
		$this->enabled = $enabled;
		return $this;
	}

	public function getLogs() {
		return $this->logs;
	}

	public function setLogs(array $logs) {
		$this->logs = $logs;
		return $this;
	}

	public function getPriorities() {
		return $this->priorities;
	}

	public function setPriorities(array $priorities) {
		$this->priorities = $priorities;
		return $this;
	}

	public function getThreshold() {
		return $this->threshold;
	}

	public function setThreshold($threshold) {
		if (is_string($threshold)) {
			$threshold = array_search($threshold, $this->priorities);
		}
		if ($threshold === null) {
			throw new InvalidArgumentException($threshold);
		}
		$this->threshold = $threshold;
		return $this;
	}

	/**
	 * Enrich context with values
	 * 
	 * @param array $context
	 * @param string $key
	 * @param string $value
	 * @return bool
	 */
	protected function context(array $context, $key, $value) {
		if (!isset($context[$key])) {
			$context[$key] = $value;
			return true;
		}
		return false;
	}

	/**
	 * Interpolate message with context
	 * 
	 * @param string $message
	 * @param array $context
	 * @return string
	 */
	protected function interpolate($message, array $context = array()) {
		$replace = array();
		foreach ($context as $key => $val) {
			$replace['{' . $key . '}'] = $val;
		}

		return strtr($message, $replace);
	}

	abstract protected function _log($level, $message, $context = array());
}